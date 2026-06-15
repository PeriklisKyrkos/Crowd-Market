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
			<h1>Εισαγωγή τιμών στη βάση δεδομένων</h1><br>
				<?php
				if(!isset($_FILES['importfile']))
				{
					echo "Δεν επιλέχθηκε κάποιο αρχείο τιμών.";
					exit;
				}
				$json_data = file_get_contents($_FILES['importfile']['tmp_name']);
				$data = json_decode($json_data, true); // decode JSON
				$prices = $data['data'];
				?>
		</div>
		<div class="w3-container" id="results_list" style="margin-top:0px;">
<?php 
			$labels = array();
			array_push ($labels, "Ονομασία προϊόντος");
			array_push ($labels, "Ημερομηνία");
			array_push ($labels, "Τιμή");
			array_push ($labels, "Αποτέλεσμα εισαγωγής");
			
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

			foreach ($prices as $price)
			{
				$pp_product = $price['name'];
				$daily_prices = $price['prices'];

				// Για κάθε προϊόν, σαρώνουμε τη λίστα των ημερομηνιών/τιμών
				// που έχουν καταχωρηθεί γι' αυτό στο αρχείο JSON
				foreach ($daily_prices as $daily_price)
				{
					$pp_date = $daily_price['date'];
					$pp_price = $daily_price['price'];

					$strSQL = "	INSERT INTO product_price 
											(
												pp_product, pp_date, pp_price
											) 
								VALUES 		(?, ?, ?)";
								
					$stmt = $conn->prepare($strSQL);
					$stmt->bind_param("ssd", $pp_product, $pp_date, $pp_price);
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
					echo "<td class='datacell'>".$pp_product."</td>";			
					echo "<td class='datacell'>".$pp_date."</td>";			
					echo "<td class='datacell'>".$pp_price."</td>";	
					echo "<td class='datacell'>".$message."</td>";					
					echo "</tr>";
				}
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
