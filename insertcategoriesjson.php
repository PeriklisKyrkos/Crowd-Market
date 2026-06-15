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
			<h1>Εισαγωγή κατηγοριών/υποκατηγοριών στη βάση δεδομένων</h1><br>
				<?php
				if(!isset($_FILES['importfile']))
				{
					echo "Δεν επιλέχθηκε κάποιο αρχείο κατηγοριών.";
					exit;
				}
				$json_data = file_get_contents($_FILES['importfile']['tmp_name']);
				$data = json_decode($json_data, true); // decode JSON
				$categories = $data['categories'];
				?>
		</div>
		<div class="w3-container" id="results_list" style="margin-top:0px;">
<?php 
			$labels = array();
			array_push ($labels, "Κωδικός κατηγορίας");
			array_push ($labels, "Κωδικός υποκατηγορίας");
			array_push ($labels, "Τίτλος");
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

			foreach ($categories as $category)
			{
				$cat_id = $category['id'];
				$cat_title = $category['name'];

				// Εισάγουμε τον κωδικό και το όνομα της κατηγορίας
				// στον πίνακα 'categories' με:
				// cat_parentID = NULL
				// cat_categoryID = $cat_id
				// cat_title = $cat_title

				$strSQL = "	INSERT INTO categories 
										(
											cat_parentID, cat_categoryID, cat_title
										) 
							VALUES 		(NULL, ?, ?)";
							
				$stmt = $conn->prepare($strSQL);
				$stmt->bind_param("ss", $cat_id, $cat_title);
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
				echo "<td class='datacell'>"."NULL"."</td>";			
				echo "<td class='datacell'>".$cat_id."</td>";			
				echo "<td class='datacell'>".$cat_title."</td>";			
				echo "<td class='datacell'>".$message."</td>";			
				echo "</tr>";
				
				$subcategories = $category['subcategories'];
				
				// Για κάθε υποκατηγορία, θέτουμε στο cat_parentID την 
				// κατηγορία που επεξεργάζεται το προηγούμενο foreach
				foreach ($subcategories as $subcategory)
				{
					$subcat_parent_id = $cat_id;
					$subcat_id = $subcategory['uuid'];
					$subcat_title = $subcategory['name'];

					$strSQL = "	INSERT INTO categories 
											(
												cat_parentID, cat_categoryID, cat_title
											) 
								VALUES 		(?, ?, ?)";
								
					$stmt = $conn->prepare($strSQL);
					$stmt->bind_param("sss",$subcat_parent_id, $subcat_id, $subcat_title);
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
					echo "<td class='datacell'>".$subcat_parent_id."</td>";			
					echo "<td class='datacell'>".$subcat_id."</td>";			
					echo "<td class='datacell'>".$subcat_title."</td>";	
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
