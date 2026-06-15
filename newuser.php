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
	<script src="js/crowdmarket.js"></script>
	
	<script>
		function checkpassword() {
			
			var code = document.getElementById("password");
			var display = document.getElementById("pwd_message");
			var strengthbar = document.getElementById("meter");
			var strength = 0;
			var password = document.getElementById("password").value;
			
			if (password.match(/[a-z]+/)) {
				strength += 1;
			}
			if (password.match(/[A-Z]+/)) {
				strength += 1;
			}
			if (password.match(/[0-9]+/)) {
				strength += 1;
			}
			if (password.match(/[$@#&!]+/)) {
				strength += 1;
			}

			switch (strength) 
			{
				case 0:
					strengthbar.value = 0;
				break;

				case 1:
					strengthbar.value = 25;
				break;

				case 2:
					strengthbar.value = 50;
				break;

				case 3:
					strengthbar.value = 75;
				break;

				case 4:
					strengthbar.value = 100;
				break;
			}
			
			if (password.length < 8) {
				display.innerHTML = "Το συνθηματικό θα πρέπει να έχει τουλάχιστον 8 χαρακτήρες";
				document.getElementById("passwordvalid").innerHTML = "0";
			}
			if (password.length >= 8) {
				display.innerHTML = "Το συνθηματικό σας καλύπτει το όριο του μήκους";
			}
			if (strength < 4) {
				display.innerHTML = "Το συνθηματικό σας δεν καλύπτει τους κανόνες πολυπλοκότητας.";
				document.getElementById("passwordvalid").innerHTML = "0";
			}
			if ((password.length >= 8) && (strength >= 4)) {
				document.getElementById("passwordvalid").innerHTML = "1";
				display.innerHTML = "";
			}
		}
	
		function insertUser() {
			if ((document.getElementById("passwordvalid").innerHTML == "1") && (document.getElementById("password").value === document.getElementById("passwordConfirmation").value))
			{
				var username = document.getElementById("username").value;
				var password = document.getElementById("password").value;
				var email = document.getElementById("email").value;
				var creds = "username="+username+"&password="+password+"&email="+email;
				var obj;
				var response;
				
				var ajx = new XMLHttpRequest();
				ajx.responseType = 'text';
				ajx.onreadystatechange = function () {
					if (ajx.readyState == 4 && ajx.status == 200) {
						response = ajx.responseText;
						response = response.trim(); 
						obj = JSON.parse(response);

						if (obj.rest_dbErrorMessage == 0)
						{
							alert("Το προφίλ σας δημιουργήθηκε.");
						}
						else
						{
							alert("Παρουσιάστηκε πρόβλημα στη δημιουργία του προφίλ σας. Επιλέξτε ένα άλλο όνομα και προσπαθήστε ξανά.");
						}
					}
				};
				ajx.open("POST", "http://localhost/crowdmarket/api.php?action=set_new_user", true);
				ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				ajx.send(creds);
			}
			else
				alert("Το συνθηματικό σας δεν είναι έγκυρο σύμφωνα με τους κανόνες. Ελέγξτε το μήκος του συνθηματικού και επιβεβαιώστε ότι η επικύρωσή του έχει γίνει σωστά.");
        }		
	</script>	
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
		<div class="w3-container" id="showcase">
		</div>
<?php
		$user = new User();
		// Εάν ο χρήστης δεν είναι ήδη συνδεδεμένος, 
		// τότε μπορεί να χρησιμοποιήσει τη φόρμα εγγραφής
		if (!$user->IsLogged())
		{
?>
			<div class="w3-container" id="showcase">
				<h1>Τα στοιχεία σας</h1><br>
				<form name="newuser"  method="POST" enctype="multipart/form-data">  
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">E-mail: </span>
						</div>
						<div class="col-75 data-column">
							<input type="email" required name="email" id="email" size="30" max="45"/> (*)
						</div>
					</div>
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">Όνομα χρήστη:</span>
						</div>
						<div class="col-75 data-column">
							<input type="text" required name="username" id="username" size="20" max="45"/> (*)
						</div>
					</div>
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">Συνθηματικό:</span>
						</div>
						<div class="col-75 data-column">
							<input type="password" required name="password" id="password" size="15" max="15" onkeyup="checkpassword();"/> (*)
						</div>
					</div>
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">Επιβεβαίωση συνθηματικού: </span>
						</div>
						<div class="col-75 data-column">
							<input type="password" required name="passwordConfirmation" id="passwordConfirmation" size="15" max="15"  onkeyup="checkpassword();"/> (*)
							<p hidden id="passwordvalid" ></p>
						</div>
					</div>
					<div class="row">
						<div class="col-25 label-column">
						</div>
						<div class="col-75 data-column">
							<progress max="100" value="0" id="meter"></progress>
							<p id="pwd_message"></p>
						</div>
					</div>
				
					<hr class="btn">
					<input type="submit" class="dbButton" name="Submit" value="Καταχώρηση" onclick="insertUser();return false;"/><input class="dbButton" type="reset" value="Επαναφορά" />
				</form><br>
			</div>

<?php
		}	// Εάν ο χρήστης δεν είναι συνδεδεμένος
		else
		{ 
?>
			<script>
				alert("Για να κάνετε εγγραφή νέου χρήστη, πρέπει πρώτα να αποσυνδεθείτε.")
			</script>
<?php 
		}
?>
	<!-- Γραμμή υποσέλιδου -->
	<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px">
		<p class="w3-right"><?php echo FOOTER_TEXT; ?></p>
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

		// Modal Image Gallery
		function onClick(element) {
			document.getElementById("img01").src = element.src;
			document.getElementById("modal01").style.display = "block";
			var captionText = document.getElementById("caption");
			captionText.innerHTML = element.alt;
		}
	</script>

</body>
</html>
