<!DOCTYPE html>
<?php 
	ob_start();
	session_start();
	require_once "includes/config.php";
	include "includes/functions.php";
?>
<html lang="en">

<head>
	<title><?php echo SITE_TITLE; ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="styles/leaflet.css"/>	
	<link rel="stylesheet" href="bootstrap/bootstrap-table/bootstrap-table.min.css">
	<link rel="stylesheet" href="styles/w3.css"/>
	<link rel="stylesheet" href="styles/crowdmarket.css"/>
	<script src="js/crowdmarket.js"></script>
</head>

<body>

	<!-- Sidebar/menu -->
	<nav class="w3-sidebar w3-red w3-collapse w3-top w3-large w3-padding" style="z-index:3;width:300px;font-weight:bold;" id="mySidebar"><br>
		<a href="javascript:void(0)" onclick="w3_close()" class="w3-button w3-hide-large w3-display-topleft" style="width:100%;font-size:22px">Close Menu</a>
		<div class="w3-container">
			<h3 class="w3-padding-32"><?php echo SITE_TITLE; ?></h3>
		</div>
		<div class="w3-bar-block">
			<?php include "menu.php"; ?>
		</div>
	</nav>

	<!-- Top menu on small screens -->
	<header class="w3-container w3-top w3-hide-large w3-red w3-xlarge w3-padding">
		<a href="javascript:void(0)" class="w3-button w3-red w3-margin-right" onclick="w3_open()">☰</a>
		<span><?php echo SITE_TITLE; ?></span>
	</header>

	<!-- Overlay effect when opening sidebar on small screens -->
	<div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay">
	</div>

	<!-- !PAGE CONTENT! -->
	<div class="w3-main" style="margin-left:340px;margin-right:40px">

	<!-- Header -->
	<div class="w3-container" id="showcase">
		<h1 class="w3-xxxlarge w3-text-red"><?php echo SITE_TITLE; ?></h1>
		<hr class="redhr w3-round">
	</div>
<?php
	$user = new User();
	if ($user->IsLogged() && $user->isAdmin())
	{
?>
		<div class="w3-container" id="showcase">
			<h1>Εισαγωγή καταστημάτων στη βάση δεδομένων</h1><br>
				<?php
				if(!isset($_FILES['importfile']))
				{
					echo "Δεν επιλέχθηκε κάποιο αρχείο καταστημάτων.";
					exit;
				}
				$json_data = file_get_contents($_FILES['importfile']['tmp_name']);
				$data = json_decode($json_data, true); // decode JSON
				$stores = $data['elements'];
				?>
		</div>
		<div class="w3-container" id="results_list" style="margin-top:0px;">
<?php 
			$labels = array();
			array_push ($labels, "ID");
			array_push ($labels, "Ονομασία");
			array_push ($labels, "Ονομασία<br>(Αγγλικά)");
			array_push ($labels, "Οδός");
			array_push ($labels, "Αριθμός");
			array_push ($labels, "ΤΚ");
			array_push ($labels, "Πόλη");
			array_push ($labels, "Γεωγρ.<br>Πλάτος");
			array_push ($labels, "Γεωγρ.<br>Μήκος");
			array_push ($labels, "Αποτέλεσμα");

			// Ρυθμίσεις του πίνακα εμφάνισης των αποτελεσμάτων
			echo "<table  data-toggle='table'
						  data-search='false'
						  data-show-search-button='false'
						  data-search-on-enter-key='false'
						  data-height='100vh'
						  data-sortable='true'
						  data-sort-class='table-active'
						  data-remember-order='true'
						  data-pagination='false'
						  data-show-extended-pagination='false'
						  data-page-number='1'
						  data-page-size='100'
						  data-show-button-text='false'
						  data-show-pagination-switch='false'
						  data-pagination-v-align='both'
						  data-show-toggle='false'
						  style='width: 100%;'>";
			echo "<thead>";
			
			// Φτιάχνει την επικεφαλίδα του πίνακα
			foreach ($labels as $label)
			{
				echo "<th data-sortable='true'>".$label."</th>";
			}
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";		

			foreach ($stores as $store)
			{
				// Διαβάζει το id του καταστήματος
				$st_id = $store['id'];
				
				// Αν η ετικέτα 'name' δεν υπάρχει, τότε για όνομα του καταστήματος
				// χρησιμοποιούμε την ετικέτα 'shop'
				if (!isset($store['tags']['name']))
				{
					$store['tags']['name'] = $store['tags']['shop'];
				}
				$st_name = $store['tags']['name'];

				// Αν η ετικέτα 'brand:en' δεν υπάρχει, 
				// το λατινικό όνομα του καταστήματος γίνεται κενό
				if (!isset($store['tags']['brand:en']))
				{
					$store['tags']['brand:en'] = "";
				}
				$st_name_en = $store['tags']['brand:en'];
				
				// Αν η ετικέτα 'addr:street' δεν υπάρχει, 
				// η οδός του καταστήματος γίνεται κενή
				if (!isset($store['tags']['addr:street']))
				{
					$store['tags']['addr:street'] = "";
				}
				$st_street = $store['tags']['addr:street'];

				// Αν η ετικέτα 'addr:housenumber' δεν υπάρχει, 
				// o αριθμός της οδού του καταστήματος γίνεται κενός
				if (!isset($store['tags']['addr:housenumber']))
				{
					$store['tags']['addr:housenumber'] = "";
				}
				$st_housenumber = $store['tags']['addr:housenumber'];

				// Αν η ετικέτα 'addr:postcode' δεν υπάρχει, 
				// o TK του καταστήματος γίνεται κενός
				if (!isset($store['tags']['addr:postcode']))
				{
					$store['tags']['addr:postcode'] = "";
				}
				$st_postcode = $store['tags']['addr:postcode'];

				// Αν η ετικέτα 'addr:city' δεν υπάρχει, 
				// η πόλη του καταστήματος γίνεται κενή
				if (!isset($store['tags']['addr:city']))
				{
					$store['tags']['addr:city'] = "";
				}
				$st_city = $store['tags']['addr:city'];

				// Γεωγραφικό πλάτος
				$st_lat = $store['lat'];
				
				// Γεωγραφικό μήκος
				$st_lon = $store['lon'];
				
				$strSQL = "	INSERT INTO stores 
										(
											st_id, st_name, st_name_en, 
											st_street, st_housenumber, st_postcode,
											st_city, st_lat, st_lon
										) 
							VALUES 		(?, ?, ?, ?, ?, ?, ?, ?, ?)";
							
				$stmt = $conn->prepare($strSQL);
				$stmt->bind_param("issssssdd", $st_id, $st_name, $st_name_en, 
											$st_street, $st_housenumber, $st_postcode,
											$st_city, $st_lat, $st_lon);
				try 
				{
					$result = $stmt->execute();
					$message = "OK";
				}
				catch(Exception $e) 
				{
					$message = $e->getMessage();
				}
				echo "<tr>";
				echo "<td class='datacell'>".$st_id."</td>";			
				echo "<td class='datacell'>".$st_name."</td>";			
				echo "<td class='datacell'>".$st_name_en."</td>";			
				echo "<td class='datacell'>".$st_street."</td>";			
				echo "<td class='datacell'>".$st_housenumber."</td>";			
				echo "<td class='datacell'>".$st_postcode."</td>";			
				echo "<td class='datacell'>".$st_city."</td>";			
				echo "<td class='datacell'>".$st_lat."</td>";			
				echo "<td class='datacell'>".$st_lon."</td>";			
				echo "<td class='datacell'>".$message."</td>";			
				echo "</tr>";
			}
			echo "</tbody>";		
			echo "</table>";		
?>
		</div>
<?php
	}
	else
	{
		echo "Πρέπει να έχετε συνδεθεί ως διαχειριστής για να χρησιμοποιήσετε αυτή τη δυνατότητα.";
	}
?>
	<!-- W3.CSS Container -->
	<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px"><p class="w3-right">
		<?php echo FOOTER_TEXT; ?></p>
	</div>

	<script>
		// Script to open and close sidebar
		function w3_open() {
			document.getElementById("mySidebar").style.display = "block";
			document.getElementById("myOverlay").style.display = "block";
		}
		 
		function w3_close() {
			document.getElementById("mySidebar").style.display = "none";
			document.getElementById("myOverlay").style.display = "none";
		}
	</script>

	<script src="js/jquery-3.3.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap-table.min.js"></script>

</body>
</html>
