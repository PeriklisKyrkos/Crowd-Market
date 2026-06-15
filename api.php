<?php
//session_start();
include "includes/config.php";
include "includes/functions.php";

define('NO_TOKEN', 'No token');
define('API_KEY', 'L7n]{f*mm]Zs5C{IGWjr!&]YZ|i%tNa1BFx,r*RgES');
define('MAX_ID', PHP_INT_MAX );

// ΠΡΟΣΟΧΗ!!!!
// Όταν είναι ενεργό το DEBUG_MODE, τότε οι κλήσεις στις API functions
// γίνονται χρησιμοποιώντας το default API Key του χρήστη default 
// Το default API Key έχει οριστεί στο define(API_KEY) (γραμμή 7 του κώδικα)
// κει έχει αποθηκευτεί στη στήλη usr_token του πίνακα users, στην εγγραφή με usr_username 'default'
//
// ΙΔΙΑΙΤΕΡΗ ΠΡΟΣΟΧΗ !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// ΠΡΙΝ ΤΗΝ ΕΓΚΑΤΑΣΤΑΣΗ ΤΟΥ API ΣΕ ΠΕΡΙΒΑΛΛΟΝ ΠΑΡΑΓΩΓΗΣ, ΠΡΕΠΕΙ Η ΜΕΤΑΒΛΗΤΗ $DEBUG_MODE ΝΑ ΠΑΡΕΙ ΤΙΜΗ FALSE
//
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

$DEBUG_MODE = true;

// An object of class RestResult is returned
// by each API call
class RestResult
{
	public $rest_dbErrorCode;
	public $rest_dbErrorMessage;
	public $rest_dbResult;
}

// GENERIC API FUNCTIONS
// Μετατρέπει το αποτέλεσμα μιας prepared statement
// σε PHP associative array
function create_resultSet ($result)
{	$resultSet = array('Result' => 'No data');
	if ($result)
	{
		$resultSet = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		{
			$resultSet[] = $row;
		}
	}
	return $resultSet;
}

// Logs the desired text into the specified filename.
// $mode: "w" for write to new file, "a" to append to existing
function logmsg ($filename, $logText, $mode)
{
	$logFile = fopen($filename, $mode);
	fwrite($logFile , $logText);
	fwrite($logFile , "\n");
	fclose($logFile);
}

// Δημιουργεί ένα τυχαίο στρινγ χαρακτήρων, το οποίο το χρησιμοποιούμε
// ως token, κάθε φορά που ένας χρήστης συνδέεται
function generateRandomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_-+={[}]\|:;?/><.,';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
// Επικυρώνει το API Key και επιστρέφει true εάν αυτό είναι έγκυρο
// διαφορετικά επιστρέφει false
function verifyKey($conn, $APIKey) 
{
	if (isset($APIKey))
	{
		$strSQL = " SELECT usr_id FROM users WHERE usr_token = ?";
		$bind_param = array($APIKey);
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("s", $APIKey);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		if ($result)
			return true;
	}
	return false;
}


// ************************************************************
// ΑΠΟ ΕΔΩ ΞΕΚΙΝΟΥΝ OI FUNCTIONS ΤΗΣ ΒΔ
// ************************************************************

// Επιστρέφει λίστα καταστημάτων που απέχουν 6km
// από την τρέχουσα θέση του χρήστη, ανεξάρτητα αν έχουν
// προσφορά ή όχι
function get_stores_from_point()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	if (isset($_POST['st_name']))
		$st_name = $_POST['st_name'];
	else
	
	$st_name = '';
	$st_name= "%".$st_name."%";
	$current_lat = $_POST['current_lat'];
	$current_lon = $_POST['current_lon'];
	
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	st_id, st_name, st_name_en, 
							st_street, st_housenumber, st_postcode, st_city, 
							st_lat, st_lon,
							products.prod_id, products.prod_name,
							bids.bid_price,
							DATE_FORMAT(DATE(bids.bid_timestamp), '%d/%m/%Y') AS 'bid_date',
							SUM(COALESCE(bid_likes.bl_like, 0)) AS 'likes', 
							SUM(COALESCE(bid_likes.bl_dislike, 0)) AS 'dislikes', 
							ST_Distance_Sphere (
								point(st_lon, st_lat),
								point(?, ?)
							) AS 'distance',
							CASE
								WHEN bid_avail.bavl_in_stock = 1 THEN 'Σε απόθεμα' 
								WHEN bid_avail.bavl_in_stock = 0 THEN 'Δεν είναι διαθέσιμο' 
								ELSE 'Δεν είναι διαθέσιμο' 
							END AS 'in_stock',
							CASE
								WHEN bid_avail.bavl_in_stock = 1 THEN 'instock.png' 
								WHEN bid_avail.bavl_in_stock = 0 THEN 'outofstock.png' 
								ELSE 'outofstock.png' 
							END AS 'in_stock_icon',
							CASE
								WHEN bids.bid_price IS NULL THEN 0 
								WHEN bids.bid_price IS NOT NULL THEN 1 
							END AS 'has_bid'
							
					FROM 	stores LEFT JOIN 
							bids ON stores.st_id=bids.bid_store LEFT JOIN
							products ON bids.bid_product=products.prod_id LEFT JOIN
							categories ON products.prod_category=categories.cat_categoryID LEFT JOIN
							bid_likes ON bid_likes.bl_bid_id=bids.bid_id LEFT JOIN
							bid_avail ON bid_avail.bavl_bid_id=bids.bid_id
					WHERE 	st_name LIKE ? AND
							ST_Distance_Sphere (
								point(st_lon, st_lat),
								point(?, ?)
							) <= 6000.00
					GROUP BY st_street, st_housenumber, st_postcode, st_city, 
							st_lat, st_lon,
							products.prod_id, products.prod_name,
							bids.bid_price, distance, 'bid_date', 'in_stock_icon', 'has_bid';";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("ddsdd", $current_lon, $current_lat, $st_name, $current_lon, $current_lat);

		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}


// Επιστρέφει λίστα με τα μοναδικά ονόματα καταστημάτων 
// που απέχουν 6kmαπό την τρέχουσα θέση του χρήστη.
// Χρησιμοποιείται για να γεμίσει η λίστα με τα ονόματα καταστημάτων.
function get_store_names_from_point()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	if (isset($_POST['st_name']))
		$st_name = $_POST['st_name'];
	else
	
	$st_name = '';
	$st_name= "%".$st_name."%";
	$current_lat = $_POST['current_lat'];
	$current_lon = $_POST['current_lon'];
	
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	DISTINCT st_name
					FROM 	stores 
					WHERE 	st_name LIKE ? AND
							ST_Distance_Sphere (
								point(st_lon, st_lat),
								point(?, ?)
							) <= 6000.00;";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("sdd", $st_name, $current_lon, $current_lat);

		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}


// Επιστρέφει λίστα καταστημάτων που απέχουν 6km
// από την τρέχουσα θέση του χρήστη, μόνο αν έχουν
// ενεργή προσφορά
function get_stores_with_bid()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	if (isset($_POST['st_name']))
		$st_name = $_POST['st_name'];
	else
		$st_name = '';
	
	$current_lat = $_POST['current_lat'];
	$current_lon = $_POST['current_lon'];
	
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	b.st_id, 
							b.st_name, 
							b.st_name_en, 
							b.st_street, b.st_housenumber, b.st_postcode, b.st_city, 
							b.st_lat, b.st_lon,
							b.prod_id,
							b.prod_name,
							b.prod_photo,
							b.bid_id,
							b.bid_price,
							b.bid_date,
							b.distance,
							COALESCE(bl.likes, 0) AS 'likes', 
							COALESCE(bl.dislikes, 0) AS 'dislikes', 
							CASE
								WHEN avail.bavl_in_stock = 1 THEN 'Σε απόθεμα' 
								WHEN avail.bavl_in_stock = 0 THEN 'Δεν είναι διαθέσιμο' 
								ELSE 'Δεν είναι διαθέσιμο' 
							END AS 'in_stock',
							CASE
								WHEN avail.bavl_in_stock = 1 THEN 'instock.png' 
								WHEN avail.bavl_in_stock = 0 THEN 'outofstock.png' 
								ELSE 'outofstock.png' 
							END AS 'in_stock_icon'
					FROM 	(
								SELECT 	st_id, st_name, st_name_en, 
										st_street, st_housenumber, st_postcode, st_city, 
										st_lat, st_lon,
										products.prod_id, products.prod_name, products.prod_photo,
										bids.bid_id,
										bids.bid_price,
										DATE_FORMAT(DATE(bids.bid_timestamp), '%d/%m/%Y') AS 'bid_date',

										ST_Distance_Sphere (
											point(st_lon, st_lat),
											point(?, ?)
										) AS 'distance'
								FROM 	stores INNER JOIN 
										bids ON stores.st_id=bids.bid_store INNER JOIN
										products ON bids.bid_product=products.prod_id INNER JOIN
										categories ON products.prod_category=categories.cat_categoryID 
								WHERE 	bid_active=TRUE AND
										ST_Distance_Sphere (
											point(st_lon, st_lat),
											point(?, ?)) <= 6000.00
							) AS b LEFT JOIN 
							(
								SELECT 	bid_likes.bl_bid_id, 
										COALESCE(SUM(bid_likes.bl_like), 0) AS 'likes',
										COALESCE(SUM(bid_likes.bl_dislike), 0) AS 'dislikes'
								FROM 	bid_likes
								GROUP BY bid_likes.bl_bid_id ) bl ON
											b.bid_id=bl.bl_bid_id LEFT JOIN 
							 (
								 SELECT 	bid_avail.bavl_bid_id, 
											bid_avail.bavl_in_stock
								 FROM 		bid_avail
							 ) AS avail ON 	bl.bl_bid_id=avail.bavl_bid_id;";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("dddd", $current_lon, $current_lat, $current_lon, $current_lat);

		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}



// Επιστρέφει λίστα προσφορών για κατάστημα με συγκεκριμένο id
function get_store_bids()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$st_id = $_POST['st_id'];
	
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	st_id, st_name, st_name_en, 
							st_street, st_housenumber, st_postcode, st_city, 
							st_lat, st_lon,
							products.prod_id, products.prod_name, products.prod_photo,
							bids.bid_id, bids.bid_price, bids.bid_user,
							DATE_FORMAT(DATE(bids.bid_timestamp), '%d/%m/%Y') AS 'bid_date',
							COALESCE(SUM(bid_likes.bl_like), 0) AS 'likes', 
							COALESCE(SUM(bid_likes.bl_dislike), 0) AS 'dislikes', 
							CASE
								WHEN bid_avail.bavl_in_stock = 1 THEN 'Σε απόθεμα' 
								WHEN bid_avail.bavl_in_stock = 0 THEN 'Δεν είναι διαθέσιμο' 
								ELSE 'Δεν είναι διαθέσιμο' 
							END AS 'in_stock',
							CASE
								WHEN bid_avail.bavl_in_stock = 1 THEN 'instock.png' 
								WHEN bid_avail.bavl_in_stock = 0 THEN 'outofstock.png' 
								ELSE 'outofstock.png'
							END AS 'in_stock_icon',
							users.usr_username AS 'Username',
                            score.sc_total_score AS 'Score'
					FROM 	stores INNER JOIN 
							bids ON stores.st_id=bids.bid_store INNER JOIN
							products ON bids.bid_product=products.prod_id INNER JOIN
							categories ON products.prod_category=categories.cat_categoryID INNER JOIN
							users ON users.usr_id=bids.bid_user INNER JOIN
                            score ON score.sc_user_id=users.usr_id LEFT JOIN
							bid_likes ON bid_likes.bl_bid_id=bids.bid_id LEFT JOIN
							bid_avail ON bid_avail.bavl_bid_id=bids.bid_id
					WHERE 	bid_active=TRUE AND
							stores.st_id=?
					ORDER BY st_id;";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("i", $st_id);

		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}



function get_bids_on_category()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	// $selected_cat = 'Καθαριότητα'; 
	$selected_cat = $_POST['selected_cat'];
	$current_lat = $_POST['current_lat'];
	$current_lon = $_POST['current_lon'];
	
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	st_id, st_name, st_name_en, 
							st_street, st_housenumber, st_postcode, st_city, 
							st_lat, st_lon,
							products.prod_id, products.prod_name,
							bids.bid_price,
							DATE_FORMAT(DATE(bids.bid_timestamp), '%d/%m/%Y') AS 'bid_date',
							COALESCE(SUM(bid_likes.bl_like), 0) AS 'likes', 
							COALESCE(SUM(bid_likes.bl_dislike), 0) AS 'dislikes', 
							ST_Distance_Sphere (
								point(st_lon, st_lat),
								point(?, ?)
							) AS 'distance',
							CASE
								WHEN bid_avail.bavl_in_stock = 1 THEN 'Σε απόθεμα' 
								WHEN bid_avail.bavl_in_stock = 0 THEN 'Δεν είναι διαθέσιμο' 
							END AS 'in_stock',
							CASE
								WHEN bid_avail.bavl_in_stock = 1 THEN 'instock.png' 
								WHEN bid_avail.bavl_in_stock = 0 THEN 'outofstock.png' 
							END AS 'in_stock_icon'
							
					FROM 	stores INNER JOIN 
							bids ON stores.st_id=bids.bid_store INNER JOIN
							products ON bids.bid_product=products.prod_id INNER JOIN
							categories ON products.prod_category=categories.cat_categoryID LEFT JOIN
							bid_likes ON bid_likes.bl_bid_id=bids.bid_id LEFT JOIN
							bid_avail ON bid_avail.bavl_bid_id=bids.bid_id
					WHERE 	bid_active=TRUE AND
							categories.cat_title=? AND
							ST_Distance_Sphere (
								point(st_lon, st_lat),
								point(?, ?)
							) <= 6000.00;";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("ddsdd", $current_lon, $current_lat, $selected_cat, $current_lon, $current_lat);

		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}


function get_products()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 		prod_id, cat.cat_title AS 'Category', subcat.cat_title AS 'Subcategory', prod_name AS 'Product'
					FROM 		products INNER JOIN
								categories AS cat  ON products.prod_category=cat.cat_categoryID INNER JOIN
								categories AS subcat ON products.prod_subcategory=subcat.cat_categoryID
					ORDER BY 	cat.cat_title, subcat.cat_title, prod_name;";
					
		$stmt = $conn->prepare($strSQL);

		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}

function get_products_by_name()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$prod_name = $_POST['prod_name'];
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 		prod_id, prod_name
					FROM 		products
					WHERE		prod_name LIKE ?
					ORDER BY 	prod_name;";
					
		$stmt = $conn->prepare($strSQL);
		$prod_name = "%".$prod_name."%";
		$stmt->bind_param("s", $prod_name);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}


function get_user_data()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$usr_id = $_POST['usr_id'];
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 		usr_id, usr_username, usr_email
					FROM 		users
					WHERE		usr_id=?;";
					
		$stmt = $conn->prepare($strSQL);
		
		$stmt->bind_param("i", $usr_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}


function get_user_likes()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$usr_id = $_POST['userid'];
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	DATE_FORMAT(bid_likes.bl_timestamp, '%d/%m/%Y') AS 'Date',
							st_name, prod_name, bid_price, 
							CASE
								WHEN bl_like = 1 THEN 'like.png'
								ELSE 'blank.png'
							END AS 'Like',
							CASE
								WHEN bl_dislike = 1 THEN 'dislike.png'
								ELSE 'blank.png'
							END AS 'Dislike'
					FROM 	bid_likes INNER JOIN 
							bids ON bid_likes.bl_bid_id=bids.bid_id INNER JOIN
							products ON bids.bid_product=products.prod_id INNER JOIN 
							stores ON bids.bid_store=stores.st_id
					WHERE	bid_active=TRUE AND
							bid_likes.bl_user_id=?;";
					
		$stmt = $conn->prepare($strSQL);
		
		$stmt->bind_param("i", $usr_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}

// Επιστρέφει το μηνιαίο και το συνολικό σκορ
// του χρήστη που έχει συνδεθεί στο σύστημα.
function get_user_score()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	//$usr_id = $_POST['usr_id'];
	$usr_id = 2;
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	COALESCE(sc_monthly_score, 0) AS 'MonthlyScore', 
							COALESCE(sc_total_score, 0) AS 'TotalScore'
					FROM 	score 
					WHERE 	sc_user_id=?;";
					
		$stmt = $conn->prepare($strSQL);
		
		$stmt->bind_param("i", $usr_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}


// Επιστρέφει τα μηνιαία και τα συνολικά tokens
// του χρήστη που έχει συνδεθεί στο σύστημα.
function get_user_tokens()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$usr_id = $_POST['usr_id'];
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	COALESCE(tok.last_month_tokens, 0) AS 'LastMonthTokens', 
							SUM(COALESCE(tok_tokens, 0)) AS 'TotalTokens'
					FROM 
							(
								SELECT  tok_user_id, tok_tokens AS last_month_tokens
								FROM tokens
								ORDER by tok_id DESC
								LIMIT 1
							) AS tok INNER JOIN tokens ON tok.tok_user_id=tokens.tok_user_id 
					WHERE 	tokens.tok_user_id=?;";
					
		$stmt = $conn->prepare($strSQL);
		
		$stmt->bind_param("i", $usr_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}


function get_monthly_bids()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$usr_month = $_POST['usr_month'];
	$usr_year = $_POST['usr_year'];
	
	// Δημιουργεί την ημερομηνία που αντιστοιχεί στην 1η του μήνα
	// του έτους που επέλεξε ο διαχειριστής
	$newDate = new DateTime();
	$newDate->setDate($usr_year, $usr_month, 1);
	$newDate = $newDate->format('Y-m-d');
	
	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	WITH recursive Date_Ranges AS (
						SELECT 	? AS Date
						UNION ALL
						SELECT 	Date + INTERVAL 1 DAY
						FROM 	Date_Ranges
						WHERE 	Date < LAST_DAY(?) 
						)
					SELECT 	Date_Ranges.Date, COUNT(bids.bid_id) AS DailyBids
					FROM 	Date_Ranges LEFT JOIN 
							bids ON Date_Ranges.Date=DATE(bids.bid_timestamp)
					GROUP BY Date_Ranges.Date;";
					
		$stmt = $conn->prepare($strSQL);
		
		$stmt->bind_param("ss", $newDate, $newDate);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}

function get_leaderboard()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// Είναι η ΜΟΝΗ αλλαγή που χρειάζεται στις συναρτήσεις get
		// ********************************************************

		$strSQL = "	SELECT 	tok.usr_username AS 'Username',
							score.sc_total_score AS 'TotalScore', 
							score.sc_monthly_score AS 'MonthlyScore', 
							tok.TotalTokens AS 'TotalTokens', 
							tok.LastMonthTokens AS 'LastMonthTokens'
					FROM score LEFT JOIN
					(
						SELECT users.usr_username, tokens.tok_user_id, SUM(tokens.tok_tokens) AS 'TotalTokens', MonthTokens.LastMonthTokens
						FROM tokens
						INNER JOIN
						(
							SELECT tokens.tok_user_id, tokens.tok_tokens AS 'LastMonthTokens'
							FROM tokens
							ORDER BY tokens.tok_id DESC
							LIMIT 1
						) AS MonthTokens ON tokens.tok_user_id=MonthTokens.tok_user_id INNER JOIN
						users ON MonthTokens.tok_user_id=users.usr_id
					GROUP BY tokens.tok_user_id) AS tok ON score.sc_user_id=tok.tok_user_id
					ORDER BY score.sc_total_score DESC";
					
		$stmt = $conn->prepare($strSQL);
		
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);

		// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
		// το οποίο έχει τα εξής properties:
		// rest_dbErrorCode: κωδικός σφάλματος
		// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
		// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
		$restResult = new RestResult();

		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	
	dbDisconnect($conn);
	return $restResult;
}

// *******************************************************************************
//
// Function: do_login();
// Δέχεται δεδομένα POST και επικυρώνει το χρήστη
// Creates a token, which is stored in the database and returns it to 
// the authenticated user. The token is required to the subsequent calls
// to execute the function
//
// *******************************************************************************
function do_login()
{
	// Δέχεται το POST array από τη φόρμα login
	//Δημιουργεί μεταβλητές username & password χωρίς escape χαρακτήρες
	if (isset($_POST['username']))
		$username = $_POST['username'];
	if (isset($_POST['password']))
		$password = $_POST['password'];

	$conn = dbConnect();
	
	// Επερώτηση που ελέγχει στον πίνακα USERS εάν υπάρχει ένα μοναδικό ζεύγος
	// username & password
	$strSQL = "	SELECT usr_id, usr_username, usr_role 
				FROM users 
				WHERE usr_username = ? AND usr_password = ?";
	
	$stmt = $conn->prepare($strSQL);
	$stmt->bind_param("ss", $username, $password);

	$stmt->execute();
	$result = $stmt->get_result();
	$errorCode = mysqli_errno($conn);
	$errorMessage = mysqli_error($conn);

	// Το αποτέλεσμα των συναρτήσεων επιστρέφεται σε ένα object,
	// το οποίο έχει τα εξής properties:
	// rest_dbErrorCode: κωδικός σφάλματος
	// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
	// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
	$restResult = new RestResult();

	// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
	// επιστρέφει τον κωδικό λάθους της Βάσης Δεδομένων και το error message που ανέφερε
	// η Βάση Δεδομένων.
	// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
	// η οποία έχει καλέσει τη συγκεκριμένη function του API.
	if (!$result)
	{
		$restResult->rest_dbResult = "NoData";
		$restResult->rest_dbErrorCode = $errorCode;
		$restResult->rest_dbErrorMessage = $errorMessage;
	}
	else
	{
		// Δημιουργία του token
		$token = generateRandomString(40);
		// Αποθήκευση του token στην εγγραφή του χρήστη στη βάση
		$strSQL = "	UPDATE users
					SET usr_token = ?
					WHERE usr_username = ? AND usr_password = ?";

		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("sss", $token, $username, $password);
		$stmt->execute();

		$strSQL = "	SELECT usr_id, usr_username, usr_token, usr_role 
					FROM users 
					WHERE usr_username = ? AND usr_password = ?";

		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("ss", $username, $password);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = 0;
		$errorMessage = 'NoErrors';

		$restResult = new RestResult();
		// Δημιουργία του associative array 
		// των αποτελεσμάτων της επερώτησης SQL
		// και του αντικειμένου $restResult
		$resultSet = create_resultSet ($result);
		$restResult->rest_dbResult = $resultSet;
		$restResult->rest_dbErrorCode = $errorCode;
		$restResult->rest_dbErrorMessage = $errorMessage;
	}
	dbDisconnect($conn);
	return $restResult;
}


// ********************************************************************
//
// ************************** INSERT FUNCTIONS ************************
//
// ********************************************************************

// Καταχώρηση νέου χρήστη.
// Καλείται από τη διαδικασία εγγραφής νέου χρήστη
function set_new_user()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$username = $_POST['username'];
	$password = $_POST['password'];
	$email = $_POST['email'];

	// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
	// το οποίο έχει τα εξής properties:
	// rest_dbErrorCode: κωδικός σφάλματος
	// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
	// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
	$restResult = new RestResult();	

	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// ********************************************************

		$strSQL = "	INSERT INTO users (usr_username, usr_password, usr_email)
					VALUES (?, ?, ?)";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("sss", $username, $password, $email);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		$stmt->close();
		
		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	dbDisconnect($conn);
	return $restResult;
}


// Καταχώρηση νέου like.
function set_like()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$bid_id = $_POST['bid_id'];
	$store_id = $_POST['store_id'];
	$prod_id = $_POST['prod_id'];
	$bid_user_id = $_POST['bid_user_id'];
	$user_id = $_POST['user_id'];

	// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
	// το οποίο έχει τα εξής properties:
	// rest_dbErrorCode: κωδικός σφάλματος
	// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
	// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
	$restResult = new RestResult();	

	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// ********************************************************

		$strSQL = "	INSERT INTO bid_likes (bl_bid_id, bl_product_id, bl_store_id, bl_user_id, bl_like)
					VALUES (?, ?, ?, ?, 1)";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("iiii", $bid_id, $prod_id, $store_id, $user_id);
	
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		$stmt->close();
		
		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		/*
		if (!$result)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		*/
		// Ενημέρωση των πόντων του προτείνοντος χρήστη, μόνο εάν η καταχώρησης
		// του like ήταν επιτυχής.
		if ($errorCode == 0)
		{
			$strSQL = "	INSERT INTO 	score (sc_user_id, sc_monthly_score, sc_total_score) VALUES (?, 5, 5)
						ON DUPLICATE KEY UPDATE
							sc_monthly_score = sc_monthly_score+5,
							sc_total_score = sc_total_score+5;";
			$stmt = $conn->prepare($strSQL);
			$stmt->bind_param("i", $bid_user_id);
			$stmt->execute();							
		}
	}
	dbDisconnect($conn);
	return $restResult;
}


// Καταχώρηση νέου dislike.
function set_dislike()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$bid_id = $_POST['bid_id'];
	$store_id = $_POST['store_id'];
	$prod_id = $_POST['prod_id'];
	$bid_user_id = $_POST['bid_user_id'];
	$user_id = $_POST['user_id'];

	// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
	// το οποίο έχει τα εξής properties:
	// rest_dbErrorCode: κωδικός σφάλματος
	// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
	// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
	$restResult = new RestResult();	

	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// ********************************************************

		$strSQL = "	INSERT INTO bid_likes (bl_bid_id, bl_product_id, bl_store_id, bl_user_id, bl_dislike)
					VALUES (?, ?, ?, ?, 1)";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("iiii", $bid_id, $prod_id, $store_id, $user_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		$stmt->close();
		
		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		// Ενημέρωση των πόντων του προτείνοντος χρήστη, μόνο εάν η καταχώρησης
		// του like ήταν επιτυχής.
		if ($errorCode == 0)
		{
			$strSQL = "	INSERT INTO 	score (sc_user_id, sc_monthly_score, sc_total_score) VALUES (?, 1, 1)
						ON DUPLICATE KEY UPDATE
							sc_monthly_score = IF (sc_monthly_score-1 < 0, 0, sc_monthly_score-1),
							sc_total_score = IF (sc_total_score-1 < 0, 0, sc_total_score-1);";
			$stmt = $conn->prepare($strSQL);
			$stmt->bind_param("i", $bid_user_id);
			$stmt->execute();							
		}
	}
	dbDisconnect($conn);
	return $restResult;
}


// Καταχώρηση νέου dislike.
function toggle_availability()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$bid_id = $_POST['bid_id'];
	$store_id = $_POST['store_id'];
	$prod_id = $_POST['prod_id'];
	$user_id = $_POST['user_id'];

	// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
	// το οποίο έχει τα εξής properties:
	// rest_dbErrorCode: κωδικός σφάλματος
	// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
	// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
	$restResult = new RestResult();	

	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// ********************************************************

		$strSQL = "	UPDATE 	bid_avail 
					SET 	bavl_in_stock = IF(bavl_in_stock=1, 0, 1),
							bavl_user_id = ?
					WHERE 	bavl_bid_id=? AND
							bavl_product_id=? AND
							bavl_store_id=?;";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("iiii", $user_id, $bid_id, $prod_id, $store_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		$stmt->close();
		
		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	dbDisconnect($conn);
	return $restResult;
}


// Ενημέρωση password υπάρχοντος χρήστη.
function update_user()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$userid= $_POST['userid'];
	$password = $_POST['password'];
	
	// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
	// το οποίο έχει τα εξής properties:
	// rest_dbErrorCode: κωδικός σφάλματος
	// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
	// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
	$restResult = new RestResult();	

	if (verifyKey($conn, $usr_token))
	{
		// ********************************************************
		// Αλλάζουμε εδώ την SQL που θέλουμε να εκτελεί 
		// η συγκεκριμένη function του API.
		// ********************************************************

		$strSQL = "	UPDATE 	users 
					SET 	usr_password=?
					WHERE 	usr_id=?";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("si", $password, $userid);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		$stmt->close();
		
		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
	}
	dbDisconnect($conn);
	return $restResult;
}


// Καταχώρηση νέας προσφοράς
function set_new_bid()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$bid_price = $_POST['bid_price'];
	$bid_product = $_POST['bid_product'];
	$bid_store = $_POST['bid_store'];
	$bid_user = $_POST['bid_user'];
	
	// Το αποτέλεσμα των συναρτήσεων get επιστρέφεται σε ένα object,
	// το οποίο έχει τα εξής properties:
	// rest_dbErrorCode: κωδικός σφάλματος
	// rest_dbErrorMessage: το μήνυμα λάθους που επιστρέφει η βάση
	// rest_dbResult: ένα associative array με τα αποτελέσματα που επέστρεψε το query
	$restResult = new RestResult();	

	if (verifyKey($conn, $usr_token))
	{
		$valid_bid = FALSE;
		// Έλεγχος εάν υπάρχει ενεργή προσφορά για το ίδιο προϊόν και στο ίδιο κατάστημα
		$strSQL = "	SELECT  COALESCE(COUNT(*), 0) AS 'ExistingBids'
					FROM 	bids
					WHERE 	bid_active=TRUE AND
							bid_product=? AND
							bid_store=? AND 
							bid_active=TRUE;";
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("ii", $bid_product, $bid_store);
		$stmt->execute();
		$result = $stmt->get_result();
		
		// Εάν δε βρέθηκε προσφορά για το ίδιο προϊόν στο ίδιο κατάστημα, 
		// η διαδικασία της καταχώρησης μπορεί να προχωρήσει
		
		$resultSet = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		{
			$resultSet[] = $row;
		}
		if ($resultSet[0]['ExistingBids'] == 0) 
			$valid_bid = TRUE;
		else
		// Έλεγχος εάν η τιμή της προσφοράς είναι 20% μικρότερη από την ενεργή
		{
			$strSQL = "	SELECT  COALESCE(MIN(bid_price), 0) AS 'MinBidPrice'
						FROM 	bids
						WHERE 	bid_active=TRUE AND
								bid_product=? AND
								bid_store=? AND 
								bid_active=TRUE;";
		
			$stmt = $conn->prepare($strSQL);
			$stmt->bind_param("ii", $bid_product, $bid_store);
			$stmt->execute();
			$result = $stmt->get_result();
			$resultSet = array();
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			{
				$resultSet[] = $row;
			}
			
			//if (!isset($resultSet[0]['MinBidPrice'])
			if ($bid_price < $resultSet[0]['MinBidPrice'])
				$valid_bid = TRUE;
		}
		if (!$valid_bid)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = -1;
			$restResult->rest_dbErrorMessage = "Η προσφορά σας δεν μπορεί να καταχωρηθεί, γιατί υπάρχει ήδη ενεργή προσφορά στο ίδιο κατάστημα για το ίδιο προϊόν";
			return $restResult;
		}
		
		// Υπολογισμός της μέσης τιμής της προηγούμενης μέρας για το προϊόν 
		$strSQL = "	SELECT 	pp_price AS 'LastDayAverage'
					FROM 	product_price INNER JOIN
                    		products ON products.prod_name=product_price.pp_product 
					WHERE 	products.prod_id=? AND
							DATE(product_price.pp_date) = CURDATE()-INTERVAL 1 DAY;";
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("i", $bid_product);
		$stmt->execute();
		$result = $stmt->get_result();
		
		$points = 0;
		// Εάν δε βρέθηκε προσφορά για το προϊόν την προηγούμενη μέρα, 
		// τότε ο χρήστης παίρνει πλήρεις πόντους
		if (!$result)
		{
			$points = 50;
		}
		else
		{
			$resultSet = array();
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			{
				$resultSet[] = $row;
			}
			if ($bid_price < $resultSet[0]['LastDayAverage']*0.8) 
				$points = 50;
		}

		// Εάν μετά τον πρώτο έλεγχο δεν βρέθηκε τιμή <20% της μέσης τιμής της προηγούμενης μέρας
		// τότε και μόνο γίνεται έλεγχος της μέσης τιμής της προηγούμενης εβδομάδας
		if ($points == 0)
		{
			// Υπολογισμός της μέσης τιμής της προηγούμενης εβδομάδας για το προϊόν 
			$strSQL = "	SELECT 	AVG(pp_price) AS 'LastWeekAverage'
						FROM 	product_price INNER JOIN
								products ON products.prod_name=product_price.pp_product
						WHERE 	bid_product=? AND
								DATE(product_price.pp_date) BETWEEN CURDATE()-INTERVAL 7 DAY AND CURDATE()-INTERVAL 1 DAY;";
			$stmt = $conn->prepare($strSQL);
			$stmt->bind_param("i", $bid_product);
			$stmt->execute();
			$result = $stmt->get_result();
			
			$points = 0;
			// Εάν δε βρέθηκε προσφορά για το προϊόν την προηγούμενη εβδομάδα, 
			// τότε ο χρήστης παίρνει πλήρεις πόντους
			if ($result)
			{
				$resultSet = array();
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
				{
					$resultSet[] = $row;
				}
				if ($bid_price < $resultSet[0]['LastWeekAverage']*0.8) 
					$points = 20;			
			}
		}
		
		$strSQL = "	INSERT INTO bids(bid_price, bid_product, bid_store, bid_user) 
					VALUES (?, ?, ?, ?)";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("diii", $bid_price, $bid_product, $bid_store, $bid_user);
		$stmt->execute();
		$last_id = $stmt->insert_id;
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		$stmt->close();
		
		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if ($errorCode!=0)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = "Η προσφορά σας καταχωρήθηκε και γι' αυτήν πήρατε ".$points." πόντους.";
			
			// Καταχώρηση των πόντων του χρήστη
			$strSQL = "	INSERT INTO 	score (sc_user_id, sc_monthly_score, sc_total_score) VALUES (?, ?, ?)
						ON DUPLICATE KEY UPDATE
							sc_monthly_score = sc_monthly_score+?,
							sc_total_score = sc_total_score+?;";
			$stmt = $conn->prepare($strSQL);
			$stmt->bind_param("iiiii", $bid_user, $points, $points, $points, $points);
			$stmt->execute();							

			// Ενημέρωση του πίνακα της διαθεσιμότητας της προσφοράς
			$strSQL = "	INSERT INTO bid_avail (bavl_bid_id, bavl_product_id, bavl_store_id, bavl_user_id) 
						VALUES (?, ?, ?, ?)";
						
			$stmt = $conn->prepare($strSQL);
			$stmt->bind_param("iiii", $last_id, $bid_product, $bid_store, $bid_user);
			$stmt->execute();
			
			$result = $stmt->get_result();
			$errorCode = mysqli_errno($conn);
			$errorMessage = mysqli_error($conn);
			$stmt->close();
		}
	}
	dbDisconnect($conn);
	return $restResult;
}


// ********************************************************************
//
// ************************** DELETE FUNCTIONS ************************
//
// ********************************************************************

function delete_bid()
{
	$conn = dbConnect();
	
	if (isset($_POST['usr_token']))
		$usr_token = $_POST['usr_token'];
	else
		$usr_token = NO_TOKEN;
	if ($GLOBALS['DEBUG_MODE']) $usr_token = API_KEY;

	$bid_id = $_POST['bid_id'];
	if (verifyKey($conn, $usr_token))
	{
		$strSQL = "	DELETE 	
					FROM	bids
					WHERE 	bid_id=?";
					
		$stmt = $conn->prepare($strSQL);
		$stmt->bind_param("i", $bid_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$errorCode = mysqli_errno($conn);
		$errorMessage = mysqli_error($conn);
		$stmt->close();
		
		// Εάν η ερώτηση δεν έτρεξε κανονικά ή δεν επέστρεψε αποτέλεσμα
		// επιστρέφει τον κωδικό λάθους της MySQL και το error message που ανέφερε
		// η MySQL.
		// Βάσει του errorCode, εμφανίζουμε μήνυμα στη σελίδα 
		// η οποία έχει καλέσει τη συγκεκριμένη function του API.
		if (!$result)
		{
			$restResult->rest_dbResult = "NoData";
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		else
		{
			// Δημιουργία του associative array 
			// των αποτελεσμάτων της επερώτησης SQL
			// και του αντικειμένου $restResult
			$resultSet = create_resultSet ($result);
			$restResult->rest_dbResult = $resultSet;
			$restResult->rest_dbErrorCode = $errorCode;
			$restResult->rest_dbErrorMessage = $errorMessage;
		}
		
	}
	dbDisconnect($conn);
	return $restResult;
}


// To array api_functions περιλαμβάνει τα ονόματα των συναρτήσεων του API που μπορούν να κληθούν από το uri
$api_functions = array ("do_login",
						"set_new_user",
						"set_new_bid",
						"get_stores_from_point",
						"get_store_names_from_point",
						"get_stores_with_bid",
						"get_products", 
						"get_products_by_name",
						"get_bids_on_category",
						"get_user_data",
						"get_user_likes",
						"get_user_score",
						"get_user_tokens",
						"get_store_bids",
						"update_user",
						"set_like",
						"set_dislike",
						"toggle_availability",
						"get_monthly_bids",
						"get_leaderboard",
						"delete_bid"
						);

// H μεταβλητή $value παίρνει τιμή ανάλογα με το αποτέλεσμα της συνάρτησης, ώστε να συνεχιστεί αντίστοιχα το πρόγραμμα
$value = "Call to undefined function.";

// Η πρακατάτω πρόταση ελέγχει το URI (μέσω του array GET), 
// ώστε να προσδιοριστεί η εντολή που θα εκτελεστεί.
// Ελέγχει το array api_functions και αν η εντολή ανηκει σε αυτό, εκτελείται
// με τις κατάλληλες παραμέτρους
if (isset($_GET["action"]) && in_array($_GET["action"], $api_functions))
{
	switch ($_GET["action"])
	{
		case "do_login":
			$value = do_login();
			break;
			
		case "set_new_user":
			$value = set_new_user();
			break;

		case "set_new_bid":
			$value = set_new_bid();
			break;

		case "get_stores_from_point":
			$value = get_stores_from_point();
			break;
			
		case "get_store_names_from_point":
			$value = get_store_names_from_point();
			break;

		case "get_stores_with_bid":
			$value = get_stores_with_bid();
			break;
			
		case "get_products":
			$value = get_products();
			break;
			
		case "get_products_by_name":
			$value = get_products_by_name();
			break;
			
		case "get_bids_on_category":
			$value = get_bids_on_category();
			break;
			
		case "get_user_data":
			$value = get_user_data();
			break;
			
		case "update_user":
			$value = update_user();
			break;
			
		case "get_user_likes":
			$value = get_user_likes();
			break;
			
		case "get_user_score":
			$value = get_user_score();
			break;
			
		case "get_user_tokens":
			$value = get_user_tokens();
			break;
			
		case "get_store_bids":
			$value = get_store_bids();
			break;
			
		case "set_like":
			$value = set_like();
			break;

		case "set_dislike":
			$value = set_dislike();
			break;

		case "toggle_availability":
			$value = toggle_availability();
			break;
			
		case "get_monthly_bids":
			$value = get_monthly_bids();
			break;

		case "get_leaderboard":
			$value = get_leaderboard();
			break;
			
		case "delete_bid":
			$value = delete_bid();
			break;
	}
}
// Το αποτέλεσμα της συνάρτησης κωδικοποιείται σε μορφή JSON array
exit(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

?>
