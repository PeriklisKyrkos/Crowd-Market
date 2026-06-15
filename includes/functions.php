
<?php

define("ADMIN", 1);
define("USER", 2);

// Κλάση User
// Δημιουργεί τις ιδιότητες και μεθόδους χειρισμού του αντικειμένου User
class User
{
	public $Usr_ID;
	public $Usr_Username;
	public $Usr_Password;
	public $Usr_Lastname;
	public $Usr_Firstname;
	public $Usr_Email;
	public $Usr_Phone;
	public $Usr_Mobile;
	public $Usr_Role_Admin;
	public $Usr_Role_Editor;
	public $Usr_Status;
	public $Usr_Prefs;

	public $User_List = array();
   
	// Διαβάζει το session cookie και επιστρέφει το ID του συνδεδεμένου χρήστη
	function GetLoggedUser()
	{
		$LoggedID = 0;
	   
		if (isset ($_SESSION['LoggedUserID']))
			$LoggedID = $_SESSION['LoggedUserID'];
		else
			$LoggedID = 0;
			
		return $LoggedID;
	}


	// Διαβάζει το session cookie και επιστρέφει το ID του συνδεδεμένου χρήστη
	function IsLogged()
	{
		if (isset ($_SESSION['LoggedUserID']))
			return true;
		return false;
	}

	// Διαβάζει το session cookie και επιστρέφει το όνομα χρήστη του συνδεδεμένου χρήστη
	function GetLoggedUsername()
	{
		if (isset ($_SESSION['LoggedUserID']))
			$LoggedUsr = $_SESSION['LoggedUsername'];
		else
			$LoggedUsr = "Guest";
		return $LoggedUsr;
	}

	function GetLoggedPhoto()
	{
		if (isset ($_SESSION['LoggedUserID']))
			$LoggedUserPhoto = $_SESSION['LoggedUserPhoto'];
		else
			$LoggedUserPhoto = "";
		return $LoggedUserPhoto;
	}
	
	function isAdmin()
	{
		if (isset ($_SESSION['LoggedUserRole'])) 
		{
			$session_role = $_SESSION['LoggedUserRole'];
			if ($session_role == ADMIN)
				return true;
		}
		return false;
	}

	function isUser()
	{
		if (isset ($_SESSION['LoggedUserRole'])) 
		{
			$session_role = $_SESSION['LoggedUserRole'];
			if ($session_role == USER)
				return true;
		}
		return false;
	}
	

}

// Function: do_logoff();
// Αποσυνδέει το χρήστη και καταστρέφει το session cookie
//
// *******************************************************************************
function do_logoff()
{
	session_unset();
	// Καταστρέφει το session και διαγράφει το cookie
	session_destroy();
	return (0);
}


function escape_numeric($var, $default) {
	if (empty($var)) {
		$var = $default;
	}
	return $var;
}

// Καθαρίζει μια μεταβλητή string, ώστε να αποφεύγεται το sql injection
function escape_alpha($conn, $data) 
{
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	$data = mysqli_real_escape_string($conn, $data);

	return $data;
}

function removeBOM ($whichString)
{
	$pos = strpos($whichString, "{",0);
	if ($pos >= 0)
		return substr($whichString,$pos);
	return $whichString;
}
?>