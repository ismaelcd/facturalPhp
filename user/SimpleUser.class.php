<?php
/********************************************
Copyright (c) 2011, Bob Kennedy
kennedy7@gmail.com
http://www.bobk4.com/simpleuser

You are free to edit and redistribute this
class as you see fit as long as the
copyright statement above if left intact.
If you like it, let me know!
********************************************/

/*
class SimpleUser
This class handles the registration, editing, deleting, and authentication of user accounts, including the display of forms neccesary for those purposes.
---------------------
Variables
---------------------
private $dbname;
Name of the DB (set in SimpleUserConfig.php)

private $dbuser;
Name of DB user (set in SimpleUserConfig.php)

private $dbpass;
DB Password (set in SimpleUserConfig.php)

private $dbhost;
Address of DB host (set in SimpleUserConfig.php)

private $prefix;
Prefix to prepend to table names (set in SimpleUserConfig.php)

private $dblink;
Connection link once DB connection is established

private $user_session;
The place inn $_SESSION where user session data can be stored (set in SimpleUserConfig.php)

private $min_pass_length;
The minimum required length for passwords (set in SimpleUserConfig.php)

---------------------
Private Functions
---------------------
set_error($msg)
Will be called when an unrecoverable error occurs. Stores the error.
get_error($msg)
Returns all errors and clears error variable.

---------------------
Public Functions
---------------------
show_login_form($post_to)
Shows a basic form with fields to allow a user to log in. $post_to is the name of the script that will be handling the login.

show_registration_form($post_to)
Shows a basic form with fields to allow a user to create thier own account. $post_to is the name of the script that will be handling the registration.

show_profile_edit_form($post_to[, $userID])
Shows a basic form with fields to allow a user to update their profile (name, email, password, etc.) $post_to is the name of the script that will be handling the updating of the profile. $userID is the ID of a user to edit (if not the current user.) This only works if the current user is an administrator. If left blank, it will alllow the current user to change their own profile. Allows admins to give admin rights to other users.

process_login()
Authenticates the user log in and sets session variables for later use by scripts.

process_registration()
Processes the submitted form data for new user creation. Makes sure logins are unique via email_exists().

process_profile_edit()
Stores changes to a logged in user's profile. Makes sure logins are unique via email_exists().

delete_account($userID)
Will delete the account of the given userID.

email_exists($email)
Checks that the given email address exists as a login in the system. Returns true if yes, false otherwise.

get_userID()
Returns the userID for the current user.

user_info($userID)
Returns the name, email, admin status and timestamp the account was created for the given user, as an array.

is_logged_in()
Returns true if the current user is logged in, false otherwise.

is_admin()
Returns true if the current user is logged in as an administrator, false otherwise.

all_users()
Returns an array of all user info (not passwords) for every user. Only available to logged in administrators.

log_out()
Logs out the currently logged in user.

*/
require_once("SimpleUserConfig.php");
class SimpleUser{

	//set in SimpleUserConfig.php
	private $dbname;
	private $dbpass;
	private $dbhost;
	private $dblink;
	private $user_session;
	private $min_pass_length;
	private $error;

	function __construct(){
		$this->dbname=USERDBNAME;
		$this->dbuser=USERDBUSER;
		$this->dbpass=USERDBPASS;
		$this->dbhost=USERDBHOST;
		$this->prefix=USERTABLEPREFIX;
		$this->user_session=USERSESSION;
		$this->min_pass_length=MINPASSLENGTH;
		//connect to db
		$this->dblink=mysql_connect($this->dbhost,$this->dbuser,$this->dbpass);
		mysql_select_db($this->dbname,$this->dblink);
	}

	private function set_error($msg){
		$this->error.= $msg."\r\n";
	}
	private function get_error(){
		return $this->error;
		$this->error='';
	}

	public function show_login_form($post_to){
		echo "<form action='".$post_to."' method='post'>
		Email: <input type='text' name='email'><br>
		Password: <input type='password' name='password'><br>
		<input type='submit' name='submit' value='Login'>
		</form>";
	}

	public function show_registration_form($post_to){
		echo "<form action='".$post_to."' method='post'>
		First Name: <input type='text' name='fname'><br>
		Last Name: <input type='text' name='lname'><br>
		Email: <input type='text' name='email'><br>
		Password: <input type='password' name='password'><br>
		Confirm Password: <input type='password' name='conf_password'><br>
		<input type='submit' name='submit' value='Register'>
		</form>";
	}
	public function show_profile_edit_form($post_to, $userID=0){
		if($userID==0)$userID=$this->get_userID();
		//make sure the user is logged in
		if($this->is_logged_in()){
			//if editing a different user's account, make sure this user is an admin
			$goahead=false;
			if($userID==$_SESSION[$this->user_session]['userID']){
				$goahead=true;
			}else{
				if($this->is_admin()){
					$goahead=true;
				}else{
					$goahead=false;
				}
			}
			//if it all checks out, do this
			if($goahead){
				$user=$this->user_info($userID);
				echo "This account was created ".date("h:ia n/j/Y",$user['created_timestamp'])."<br><form action='".$post_to."' method='post'><input type='hidden' name='userID' value='".$userID."'>
				First Name: <input type='text' name='fname' value='".$user['fname']."'><br>
				Last Name: <input type='text' name='lname' value='".$user['lname']."'><br>
				Email: <input type='text' name='email' value='".$user['email']."'><br>";
				//if the current user is an administrator, let them give this person admin privileges
				if($this->is_admin()){
					echo "<input type='checkbox' name='is_admin' value='true' ".($user['is_admin']==1?"checked":"")."> Administrator<br>";
				}
				echo "-- Leave the following blank unless you'd like to change your password --<br>
				New Password: <input type='password' name='new_password'><br>
				Confirm Password: <input type='password' name='conf_password'><br>
				<input type='submit' name='submit' value='Save'>
				</form>";
			}else{
				$this->set_error("You are not allowed to do that");
				return false;
			}
		}else{
			$this->set_error("You are not logged in");
				return false;
		}
	}
	public function process_login(){
		$sql="select userID from ".$this->prefix."user_creds where email='".mysql_real_escape_string($_POST['email'])."' and pass='".md5($_POST['password'])."'";
		$rs=mysql_query($sql);
		if(mysql_num_rows($rs)>0){
			$r=mysql_fetch_assoc($rs);
			$userinfo=$this->user_info($r['userID']);
			$_SESSION[$this->user_session]=$userinfo;
			return true;
		}else{
			return "Incorrect username/password.";
		}
	}
	public function process_registration(){
		if(strlen($_POST['email'])>4){
			if(strlen($_POST['conf_password'])>=$this->min_pass_length){
				if($_POST['password']==$_POST['conf_password']){
					if(!$this->email_exists($_POST['email'])){
						//it's all good, put it in the DB
						$sql="insert into ".$this->prefix."users values (
						'',
						'".mysql_real_escape_string(htmlentities($_POST['fname'],ENT_QUOTES))."',
						'".mysql_real_escape_string(htmlentities($_POST['lname'],ENT_QUOTES))."',
						'".mysql_real_escape_string($_POST['email'])."',
						'0',
						".mktime().")";
						$rs=mysql_query($sql);
						$newID=mysql_insert_id();
						$sql="insert into ".$this->prefix."user_creds values(
						'".$newID."',
						'".mysql_real_escape_string($_POST['email'])."',
						'".md5($_POST['password'])."')";
						$rs=mysql_query($sql);
						return true;
					}else{
						return "That email address already exists in our system. Please choose a different one.";
					}
				}else{
					return "You didn't type the same password twice.";
				}
			}else{
				return "Your password must be longer than ".$this->min_pass_length." characters.";
			}
		}else{
			return "A valid email address is required.";
		}
	}
	public function process_profile_edit(){
		//if this isn't the profile for the current user, make sure we're logged in as an admin
		if($_POST['userID']==$this->get_userID() or ($_POST['userID']!=$this->get_userID() and $this->is_admin()) ){
			if(strlen($_POST['email'])>4){
			$info=$this->user_info($_POST['userID']);
				if(!$this->email_exists($_POST['email']) or $_POST['email']==$info['email']){
					//do the basic info stuff, only let admins change the "is_admin" field
					$sql="update ".$this->prefix."users set
					fname='".mysql_real_escape_string(htmlentities($_POST['fname'],ENT_QUOTES))."',
					lname='".mysql_real_escape_string(htmlentities($_POST['lname'],ENT_QUOTES))."',
					email='".mysql_real_escape_string($_POST['email'])."'
					".($this->is_admin()?", is_admin='".($_POST['is_admin']==true?"1":"0")."'":"")."
					where userID=".$_POST['userID']."";
					$rs=mysql_query($sql);
					if($_POST['userID']==$this->get_userID()){
						$userinfo=$this->user_info($this->get_userID());
						$_SESSION[$this->user_session]=array();
						$_SESSION[$this->user_session]=$userinfo;
					}
				}else{
					return "That email address already exists in our system. Please choose a different one.";
				}
			}else{
				return "A valid email address is required.";
			}
			if(strlen($_POST['conf_password'])>=$this->min_pass_length and strlen($_POST['conf_password'])>0){
				if($_POST['new_password']==$_POST['conf_password']){
					$sql="update ".$this->prefix."user_creds set
					email='".mysql_real_escape_string($_POST['email'])."',
					pass='".md5($_POST['password'])."'
					where userID=".$_POST['userID']."";
					$rs=mysql_query($sql);
					return true;
				}else{
					return "You didn't type the same password twice.";
				}
			}elseif(strlen($_POST['conf_password'])>0){
				return "Your password must be longer than ".$this->min_pass_length." characters.";
			}
		}else{
			$this->set_error("You are not allowed to do that!");
			return false;
		}
		return true;
	}
	public function delete_account($userID){
		//delete the given account
		if($this->is_admin()){
			$sql="delete from ".$this->prefix."users where userID=".$userID;
			if($rs=mysql_query($sql)){}else{$this->set_error("Couldn't delete that record from users table."); return false;}
			$sql="delete from ".$this->prefix."user_creds where userID=".$userID;
			if($rs=mysql_query($sql)){}else{$this->set_error("Couldn't delete that record from user_creds table."); return false;}
			return true;
		}
	}
	public function email_exists($email){
		//return true if the given email address exists in the system
		$sql="select userID from ".$this->prefix."users where email='".mysql_real_escape_string($email)."'";
		$rs=mysql_query($sql);
		if(mysql_num_rows($rs)>0){
			return true;
		}else return false;
	}
	public function get_userID(){
		//return the userID of the currently logged in user
		return $_SESSION[$this->user_session]['userID'];
	}
	public function user_info($userID){
		//return all info for the given user
		$sql="select * from ".$this->prefix."users where userID=".$userID;
		$rs=mysql_query($sql);
		$r=mysql_fetch_assoc($rs);
		return $r;
	}
	public function is_logged_in(){
		//return true or false
		if(isset($_SESSION[$this->user_session]['userID']) and $_SESSION[$this->user_session]['userID']>0)return true;
		else return false;
	}
	public function is_admin(){
		//return true or false
		if(isset($_SESSION[$this->user_session]['is_admin']) and $_SESSION[$this->user_session]['is_admin']==1)return true;
		else return false;
	}
	public function all_users(){
		//return an array of all user's info
		$sql="select * from ".$this->prefix."users";
		$rs=mysql_query($sql);
		while($r=mysql_fetch_assoc($rs)){
			$ret[$r['userID']]=$r;
		}
		return $ret;
	}
	public function log_out(){
		//log out the current user
		unset($_SESSION[$this->user_session]);
		$_SESSION[$this->user_session]=array();
		return true;
	}
}
?>