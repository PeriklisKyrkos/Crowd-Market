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
	<link rel="stylesheet" href="styles/w3.css"/>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins">
	<link rel="stylesheet" href="styles/bootstrap.min.css">
	<link rel="stylesheet" href="styles/crowdmarket.css"/>
	<script src="js/crowdmarket.js"></script>
	
	<script>
	
		function getLeaderboard() {
			var creds = "";
			var obj;
			var response;
			var tablerow;
			
			var ajx = new XMLHttpRequest();
			ajx.responseType = 'text';
			ajx.onreadystatechange = function () {
				if (ajx.readyState == 4 && ajx.status == 200) {
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					
					var data = obj.rest_dbResult;
					if ((typeof data !== 'undefined') & (data !== null)) {
						for (let i = 0; i < data.length; i++) {
							tablerow = "<tr>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].Username + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].TotalScore + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].MonthlyScore + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].TotalTokens + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].LastMonthTokens + "</td>";
							tablerow = tablerow + "</tr>";
						}
						var tbodyRef = document.getElementById('leaderboard').getElementsByTagName('tbody')[0];
						tbodyRef.innerHTML = tablerow;
					}
				}
			};
			ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_leaderboard", true);
			ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			ajx.send(creds);
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
			<h1 class="w3-xxxlarge w3-text-red"><?php echo "Leaderboard"; ?></h1>
			<hr class="redhr w3-round">
		</div>

<?php
		$user = new User();
		// Εάν ο χρήστης δεν είναι ήδη συνδεδεμένος, 
		// τότε μπορεί να χρησιμοποιήσει τη σελίδα αν είναι διαχειριστής
		if ($user->IsLogged() && $user->isAdmin())
		{
?>
			<div class="row">
				<div class='w3-half w3-margin-bottom'>
					<table  id='leaderboard' style='width: 70%;'
					  data-show-search-button='true'
					  data-height='460'
					  data-sortable='true'
					  data-sort-class='table-active'
					  data-remember-order='true'
					  data-pagination='true'
					  data-show-extended-pagination='true'
					  data-page-number='1'
					  data-page-size='30'
					  data-show-button-text='true'
					  data-show-pagination-switch='true'
					  data-pagination-v-align='both'
					  data-show-toggle='true'
					  style = 'height: 100vh'>
						<thead>
							<tr>
								<th class='labelcell' data-sortable='true'>Χρήστης</th>
								<th class='labelcell' data-sortable='true'>Συνολικό<br> σκορ</th>
								<th class='labelcell' data-sortable='true'>Σκορ μήνα</th>
								<th class='labelcell' data-sortable='true'>Συνολικά <br>tokens</th>
								<th class='labelcell' data-sortable='true'>Tokens<br>τελευταίου<br>μήνα</th>
								<th class='labelcell' data-sortable='true'></th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
<?php
		}	// Εάν ο χρήστης δεν είναι συνδεδεμένος
		else
		{ 
?>
			<script>
				alert("Για να δείτε το ιστορικό των likes που έχετε κάνει, πρέπει πρώτα να συνδεθείτε.");
			</script>
<?php 
		}
?>
	<!-- Γραμμή υποσέλιδου -->
	<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px">
		<p class="w3-right"><?php echo FOOTER_TEXT; ?></p>
	</div>

	<script>
	
		getLeaderboard();

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
