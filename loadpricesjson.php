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
	<link rel="stylesheet" href="styles/w3.css"/>
	<link rel="stylesheet" href="styles/crowdmarket.css"/>
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
				<h1>Ανέβασμα αρχείου τιμών προϊόντων</h1><br>
					<form action="insertpricesjson.php" method="post" enctype="multipart/form-data">
						<div class="row">
							<label for="frm_importfile">Επιλέξτε αρχείο τιμών προϊόντων (JSON):</label>
						</div>
						<div class="row">
							<input type="file" name="importfile" id="frm_importfile">
						</div>
						<div class="row" style="margin-top: 40px;">
							<button type="submit" class="dbButton">Ανέβασμα</button>
						</div>
					</form>
				<br>
			</div>
<?php
		}
		else
		{
			echo "Πρέπει να έχετε δικαιώματα Διαχειριστή για να χρησιμοποιήσετε αυτή τη σελίδα.";
		}
?>
		<!-- W3.CSS Container -->
		<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px"><p class="w3-right">
			<?php echo FOOTER_TEXT; ?></p>
		</div>
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


</body>
</html>
