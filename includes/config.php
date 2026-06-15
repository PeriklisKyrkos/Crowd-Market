<?php
// Έναρξη συνόδου
if(!isset($_SESSION)) 
{ 
	session_start(); 
} 

// Εμφάνιση μηνυμάτων php
ini_set('display_errors', 'On');
// Εμφάνιση όλων των μηνυμάτων σφάλματος εκτέλεσης php
error_reporting(E_ALL);
define('SITE_TITLE','CrowdMarket');
define('FOOTER_TEXT','Συνεργατική πλατφόρμα αναζήτησης προσφορών');
define('IMAGE_FOLDER','images/');

// Διεύθυνση του web server ή ip address του
define('WEB_SERVER', 'http://localhost/crowdmarket/');
// Στοιχεία βάσης δεδομένων
// Server
define('DBHOST','localhost');
// Όνομα χρήστη της βάσης δεδομένων με το οποίο γίνεται η σύνδεση της εφαρμογής
define('DBUSER','root');
// Συνθηματικό χρήστη της βάσης
define('DBPASS','');
// Όνομα της βάσης δεδομένων
define('DBNAME','crowdmarket');

// Σύνδεση με τη βάση δεδομένων
$conn = mysqli_connect (DBHOST, DBUSER, DBPASS);
mysqli_select_db ($conn, DBNAME);

// Εάν η σύνδεση με τη βάση δεν είναι επιτυχής, εμφάνιση μηνύματος και διακοπή του προγράμματος
if(!$conn)
{
    die( "Δεν είναι δυνατή η σύνδεση με τη βάση δεδομένων.");
}

// Ορισμός του character set της βάσης σε UTF8
mysqli_query($conn, "SET NAMES UTF8");

function dbConnect () 
{
	static $dbconn;
	if ($dbconn===NULL){ 
		$dbconn = mysqli_connect (DBHOST, DBUSER, DBPASS, DBNAME);
	}
	return $dbconn;
}

function dbDisconnect ($connection)
{
	mysqli_close($connection);
}

?>
