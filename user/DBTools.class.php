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
class DBTools
This class handles the creation of the database, necessary tables and first administrator account.

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

private $dblink;
Connection link once DB connection is established

private $goodmsg;
Tracks setup process to be output for later debugging

---------------------
Private Functions
---------------------
make_tables()
Does the actual dirty work of creating the tables if they don't exist. Called from set_up_db().

throw_error($msg)
Will be called when an unrecoverable error occurs. Outputs the error to the screen and exits script exectution.

---------------------
Public Functions
---------------------
set_up_db()
Creates the DB according to options in SimpleUserConfig.php, if it doesn't exist already

check_db()
Checks all DB/table configuration. Also makes sure an admin account exists.

create_admin_account($email, $password, $firstname='Admin',$lastname='Istrator')
Creates the first admin account in the DB. $email and $password are required.

*/
require_once("SimpleUserConfig.php");

class DBTools{

	private $dbname;
	private $dbuser;
	private $dbpass;
	private $dbhost;
	private $dblink;
	private $goodmsg;

	function __construct(){
		$this->dbname=USERDBNAME;
		$this->dbuser=USERDBUSER;
		$this->dbpass=USERDBPASS;
		$this->dbhost=USERDBHOST;
		$this->prefix=USERTABLEPREFIX;
		if($this->dbname=="..." or $this->dbuser=="..." or $this->dbpass=="..." or $this->dbhost=="..."){
			$this->throw_error("Please change the database connection values in SimpleUserConfig.php then run this script again.");
		}
		//connect to db
		$this->dblink=mysql_connect($this->dbhost,$this->dbuser,$this->dbpass) or $this->throw_error("Couldn't connect to database host");
		//try to connect to the db
		mysql_select_db($this->dbname);
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////
	//private functions
	///////////////////////////////////////////////////////////////////////////////////////////////////

	private function make_tables(){
	$this->goodmsg.="<b>Running make_tables()</b><br>";
		$sql="CREATE TABLE IF NOT EXISTS `".$this->prefix."users` (
			`userID` bigint(20) NOT NULL AUTO_INCREMENT,
			`fname` varchar(100) NULL,
			`lname` varchar(100) NULL,
			`email` varchar(100) NOT NULL,
			`is_admin` tinyint(1) DEFAULT 0 NOT NULL,
			`created_timestamp` int NULL,
			PRIMARY KEY (`userID`),
  			UNIQUE KEY `email` (`email`)
		)";
		$rs1=mysql_query($sql) or $this->throw_error("Error creating users table: ".mysql_error());
		$sql="CREATE TABLE IF NOT EXISTS `".$this->prefix."user_creds` (
			`userID` bigint(20) NOT NULL,
			`email` varchar(255) NOT NULL,
			`pass` varchar(255) NULL,
			PRIMARY KEY (`userID`),
 			UNIQUE KEY `email` (`email`)
		)";
		$rs2=mysql_query($sql) or $this->throw_error("Error creating user_creds table: ".mysql_error());
		$this->goodmsg.="Tables set up successfully<br>";
	}

	private function throw_error($msg){
		echo $this->goodmsg."<span style='color:red;'>".$msg."</span><br>";
		exit;
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////
	//public functions
	///////////////////////////////////////////////////////////////////////////////////////////////////

	public function set_up_db(){
	$this->goodmsg.="<b>Running set_up_db()</b><br>";
		//connect to the db
		$exists=mysql_select_db($this->dbname);
		if($exists){
			//successful connection, create all the tables
			$this->make_tables();
		}else{
			//the DB isn't there, create it
			$sql="CREATE DATABASE IF NOT EXISTS `".$this->dbname."`";
			$rs=mysql_query($sql) or $this->throw_error("Error creating database: ".mysql_error());
			$this->goodmsg.="Database set up<br>";
			//connect to the new db
			mysql_select_db($this->dbname);
			//they shouldn't be there, so make the tables
			$this->make_tables();
		}
		echo  $this->goodmsg;
		$this->goodmsg='';
	}

	public function check_db(){
	$this->goodmsg.="<b>Running check_db()</b><br>";
		//make sure SimpleUserConfig.php has been changed
		if($this->dbname=="..." or $this->dbuser=="..." or $this->dbpass=="..." or $this->dbhost=="..."){
			$this->throw_error("Your database connection info needs to be changed in SimpleUserConfig.php");
		}else $this->goodmsg.="Connection settings look ok<br>";
		//make sure we can connect to the db host
		if(!$this->dblink)$this->throw_error("Couldn't connect to the database host specified in SimpleUserConfig.php");
		else $this->goodmsg.="Connected to DB host<br>";
		//make sure we can select the database
		if(!mysql_select_db($this->dbname,$this->dblink))$this->throw_error("Couldn't select the database specified in SimpleUserConfig.php");
		else $this->goodmsg.="Database selected<br>";

		//make sure the right tables exist
		$sql="show tables";
		$rs=mysql_query($sql);
		$users_table_found=false;
		$user_creds_table_found=false;
		while($r=mysql_fetch_assoc($rs)){
			//make sure they have the right fields
			if($r['Tables_in_'.$this->dbname]==$this->prefix."users"){
				$users_table_found=true;
				$sql2="describe ".$this->prefix."users";
				$rs2=mysql_query($sql2);
				while($r2=mysql_fetch_assoc($rs2)){
					$fields[]=$r2['Field'];
				}
				if(!in_array("userID",$fields) or !in_array("fname",$fields) or !in_array("lname",$fields) or !in_array("email",$fields) or !in_array("is_admin",$fields) or !in_array("created_timestamp",$fields)){
					$this->throw_error("Your `users` table does not match the requirements and was probably not created by this script. Please make sure the prefix set in SimpleUserConfig.php is correct.");
				}else $this->goodmsg.="Users table is formatted correctly<br>";
			}elseif($r['Tables_in_'.$this->dbname]==$this->prefix."user_creds"){
				$user_creds_table_found=true;
				$sql3="describe ".$this->prefix."user_creds";
				$rs3=mysql_query($sql3);
				while($r3=mysql_fetch_assoc($rs3)){
					$fields2[]=$r3['Field'];
				}
				if(!in_array("userID",$fields2) or !in_array("email",$fields2) or !in_array("pass",$fields2)){
					$this->throw_error("Your `user_creds` table does not match the requirements and was probably not created by this script. Please make sure the prefix set in SimpleUserConfig.php is correct.");
				}else $this->goodmsg.="User credentials table is formatted correctly<br>";
			}
		}
		if($users_table_found and $user_creds_table_found){
			$this->goodmsg.="Both required tables were found<br>";
		}else{
			$this->throw_error("One of the required tables is missing. Please try running this script again.");
		}
		$sql="select email from ".$this->prefix."users where is_admin=1 limit 1";
		$rs=mysql_query($sql);
		$r=mysql_fetch_assoc($rs) or $this-throw_error("No administrator account exists. Use create_admin_account() to create one.");
		$this->goodmsg.="At least one administrator account exists. Email: ".$r['email']."<br>";
		$this->goodmsg.="<span style='font-weight:bold; color:darkgreen;'>All configuration options check out fine. You are set up and ready to go! Look at example.php and documentation.html for usage.</span><br>";
		echo  $this->goodmsg;
		$this->goodmsg='';
	}
	public function create_admin_account($email, $password, $firstname='Admin',$lastname='Istrator'){
		$this->goodmsg.="<b>Running create_admin_account()</b><br>";
		$sql="insert into ".$this->prefix."users values ('','".mysql_real_escape_string(htmlentities($firstname,ENT_QUOTES))."','".mysql_real_escape_string(htmlentities($lastname,ENT_QUOTES))."','".mysql_real_escape_string($email)."','1',".mktime().")";
		$rs=mysql_query($sql) or $this->throw_error("Error insterting in ".$this->prefix."users table: ".mysql_error());
		$newID=mysql_insert_id();
		$sql="insert into ".$this->prefix."user_creds values('".$newID."','".mysql_real_escape_string($email)."','".md5($password)."')";
		$rs=mysql_query($sql) or $this->throw_error("Error creating admin account: ".mysql_error());
		$this->goodmsg.="Administrator account successfully created!<br>";
		echo  $this->goodmsg;
		$this->goodmsg='';
	}
}

?>