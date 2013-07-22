<?php
//for more info, go to http://www.bobk4.com/simpleuser

///////////////////////////////////////////////////////////////////////////////////////
//TO SET UP THE DATABASE AND FIRST ADMINISTRATOR
///////////////////////////////////////////////////////////////////////////////////////

require('DBTools.class.php');
$dbt=new DBTools;
//create DB and tables
$dbt->set_up_db();
//create the administrator account (only email and password are required)
$dbt->create_admin_account("admin@email.com","Adm!nP@$$","Firstname","Lastname");
//check to make sure that everything is happy with the db, including an admin account
$dbt->check_db();

/*
///////////////////////////////////////////////////////////////////////////////////////
//USER MANAGEMENT
///////////////////////////////////////////////////////////////////////////////////////

session_start();
require('SimpleUser.class.php');
$u=new SimpleUser;

///////////////////////////////////////////////////////////////////////////////////////
//ADMIN STUFF: TO SHOW A TABLE OF ALL USERS
///////////////////////////////////////////////////////////////////////////////////////

//show a table of all registered users
$userA=$u->all_users();
echo "All registered users<br>
<table><tr><th>First Name</th><th>Last Name</th><th>Email</th><th>Is Admin</th><th>UserID</th><th>Created</th></tr>";
foreach($userA as $userID=>$user){
	echo "<tr><td>".$user['fname']."</td><td>".$user['lname']."</td><td>".$user['email']."</td><td>".($user['is_admin']==0?"No":"Yes")."</td><td>".$userID."</td><td>".date("h:ia n/j/Y",$user['created_timestamp'])."</td></tr>";
}
echo "</table>";


///////////////////////////////////////////////////////////////////////////////////////
//TO REGISTER A NEW USER
///////////////////////////////////////////////////////////////////////////////////////

//you could do this if a user wants to register
if(!isset($_POST['email'])){
	//show the user registration form
	echo "Create an account!<br>";
	$u->show_registration_form($_SERVER['SCRIPT_NAME']);
}elseif(!$u->is_logged_in() and isset($_POST['email'])){
	//process the user registration
	$returned=$u->process_registration();
	if($returned===true){
		echo "Registration complete!<br>";
	}else{
		echo "There was a problem with your registration. ".$returned." Please try again!<br>";
		$u->show_registration_form($_SERVER['SCRIPT_NAME']);
	}
}


///////////////////////////////////////////////////////////////////////////////////////
//TO LET A USER LOG IN
///////////////////////////////////////////////////////////////////////////////////////

//check if the user is signed in
if(!$u->is_logged_in() and !isset($_POST['email'])){
	echo "Log in!<br>";
	//show the user login form
	$u->show_login_form($_SERVER['SCRIPT_NAME']);
}elseif(!$u->is_logged_in() and isset($_POST['email'])){
	//process the user login
	$returned=$u->process_login();
	if($returned===true){
		//get the userID
		echo "Login successful! Your userID is: ".$u->get_userID();
	}else{
		echo "Login failed. ".$returned." Try again!<br>";
		$u->show_login_form($_SERVER['SCRIPT_NAME']);
	}
}


///////////////////////////////////////////////////////////////////////////////////////
//TO EDIT A USER'S PROFILE
///////////////////////////////////////////////////////////////////////////////////////

//you could do this if a user wants to edit their info
if($u->is_logged_in() and !isset($_POST['email'])){
	//show the profile edit form
	$u->show_profile_edit_form($_SERVER['SCRIPT_NAME']);
}elseif($u->is_logged_in() and isset($_POST['email'])){
	//process the profile edit
	$returned=$u->process_profile_edit();
	if($returned===true){
		echo "Your profile was succefully updated!<br>";
	}else{
		echo "Your profile could not be updated for some reason. ".$returned." Try again.<br>";
		$u->show_profile_edit_form($_SERVER['SCRIPT_NAME']);
	}
}
*/
?>