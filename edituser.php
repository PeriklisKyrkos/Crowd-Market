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
	
		function getUser() {
            var usr_id = document.getElementById("LoggedUserID").value;
            var creds = "usr_id="+usr_id;
			var obj;
			var response;
			
            var ajx = new XMLHttpRequest();
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () {
                if (ajx.readyState == 4 && ajx.status == 200) {
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					
					var data = obj.rest_dbResult;
					if ((typeof data !== 'undefined') & (data !== null)) {
						for(let i = 0; i < data.length; i++) {
							document.getElementById("email").value = data[i].usr_email;
							document.getElementById("username").value = data[i].usr_username;
						}
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_user_data", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
        }
		
		function updateUser() 
		{
			if (document.getElementById("passwordvalid").value == 1)
			{
				var usr_id = document.getElementById("LoggedUserID").value;
				var usr_password = document.getElementById("password").value;
				var usr_password_conf = document.getElementById("passwordConfirmation").value;
				var creds = "userid="+usr_id+"&password="+usr_password;
				var obj;
				var response;
				
				var ajx = new XMLHttpRequest();
				ajx.responseType = 'text';
				ajx.onreadystatechange = function () {
					if (ajx.readyState == 4 && ajx.status == 200) {
						response = ajx.responseText;
						response = response.trim(); 
						obj = JSON.parse(response);
						
						var data = obj.rest_dbErrorCode;
						if ((typeof data !== 'undefined') & (data !== null)) {
							if (data == 0)
								alert("Το συνθηματικό σας άλλαξε.");
							}
						}
					}
				ajx.open("POST", "http://localhost/crowdmarket/api.php?action=update_user", true);
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
		if ($user->IsLogged())
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
							<input type="email" readonly name="email" id="email" size="30" max="45"/> (*)
						</div>
					</div>
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">Όνομα χρήστη:</span>
						</div>
						<div class="col-75 data-column">
							<input type="text" readonly name="username" id="username" size="20" max="45"/> (*)
						</div>
					</div>
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">Συνθηματικό:</span>
						</div>
						<div class="col-75 data-column">
							<input type="password" required name="password" id="password" size="15" max="15" onkeyup="checkpassword()"/> (*)
						</div>
					</div>
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">Επιβεβαίωση συνθηματικού: </span>
						</div>
						<div class="col-75 data-column">
							<input type="password" required name="passwordConfirmation" id="passwordConfirmation" size="15" max="15"/> (*)
							<input type="hidden" id="passwordvalid">
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
					<input type="submit" class="dbButton" name="Submit" value="Καταχώρηση" onclick="updateUser();return false;"/><input class="dbButton" type="reset" value="Επαναφορά" />
				</form><br>
			</div>

<?php
		}	// Εάν ο χρήστης δεν είναι συνδεδεμένος
		else
		{ 
?>
			<script>
				alert("Για να επεξεργαστείτε το προφίλ σας, πρέπει πρώτα να συνδεθείτε.")
			</script>
<?php 
		}
?>
	<!-- Γραμμή υποσέλιδου -->
	<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px">
		<p class="w3-right"><?php echo FOOTER_TEXT; ?></p>
	</div>

	<script>
	
		getUser();
		
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
