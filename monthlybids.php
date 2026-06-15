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
	<script src="js/chart.js"></script>	
	<script src="js/crowdmarket.js"></script>
	
	<script>
	
		function getMonthlyBids() 
		{
			user_month = document.getElementById("bids-month").value;
			user_year = document.getElementById("bids-year").value;
			var creds = "usr_month="+user_month+"&usr_year="+user_year;
			var obj;
			var response;
			var data;
			var tablerow="";
			var ajx = new XMLHttpRequest();
			ajx.responseType = 'text';
			ajx.onreadystatechange = function () {
				if (ajx.readyState == 4 && ajx.status == 200) {
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					data = obj.rest_dbResult;
					
					if (data.length == 0)
						tablerow="Δεν υπάρχουν προσφορές για τον μήνα που επιλέξατε.";
					if ((typeof data !== 'undefined') & (data !== null)) 
					{
						for (var i = 0; i < data.length; i++) {
							
							tablerow = tablerow + "<tr>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].Date + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].DailyBids + "</td>";
							tablerow = tablerow + "</tr>";
						}
						var tbodyRef = document.getElementById('data').getElementsByTagName('tbody')[0];
						tbodyRef.innerHTML = tablerow;
					}
					
					// Δημιουργία του γραφήματος
					var xValues = [];
					var yValues = [];
					var barColors = [];
					for (var i = 0; i < data.length; i++) 
					{
						xValues.push(data[i].Date);
						yValues.push(data[i].DailyBids);
						barColors.push("red");
					}

					new Chart("chartContainer", 
						{
							type: "bar",
							data: {
							labels: xValues,
							datasets: 
							[{
								backgroundColor: barColors,
								data: yValues
							}]
						},
						options: 
						{
							legend: 
							{
								display: false
							},
							title: 
							{
								display: true,
								text: "Πλήθος προσφορών ανά ημέρα"
							}
						}
					});					
				}
			};
			ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_monthly_bids", true);
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
			<h1 class="w3-xxxlarge w3-text-red"><?php echo "Προσφορές μήνα ανά ημέρα"; ?></h1>
			<hr class="redhr w3-round">
		</div>

<?php
		$user = new User();
		// Εάν ο χρήστης δεν είναι ήδη συνδεδεμένος, 
		// τότε μπορεί να χρησιμοποιήσει τη φόρμα εγγραφής
		if ($user->IsLogged() && $user->isAdmin())
		{
?>
			<div class="w3-container" id="showcase">
				<h1>Επιλέξτε μήνα και έτος για να δείτε τις ημερήσιες προσφορές</h1><br>
				<form name="monthlybids"  method="POST" enctype="multipart/form-data">  
					<div class="row">
						<div class="col-25 label-column">
							<span class="black">Μήνας </span>
							<select name="bids-month" id="bids-month" > 
								<option value="1">Ιανουάριος</option>
								<option value="2">Φεβρουάριος</option>
								<option value="3">Μάρτιος</option>
								<option value="4">Απρίλιος</option>
								<option value="5">Μάιος</option>
								<option value="6">Ιούνιος</option>
								<option value="7">Ιούλιος</option>
								<option value="8">Αύγουστος</option>
								<option value="9">Σεπτέμβριος</option>
								<option value="10">Οκτώβριος</option>
								<option value="11">Νοέμβριος</option>
								<option value="12">Δεκέμβριος</option>
							</select>
						</div>
						<div class="col-25 label-column">
							<span class="black">Έτος</span>
							<select name="bids-year" id="bids-year" > 
								<option value="2023">2023</option>
								<option value="2024">2024</option>
								<option value="2025">2025</option>
							</select>
							<input type="submit" class="dbButton" name="Submit" value="Αποτελέσματα" style="margin-left: 30px;" onclick="getMonthlyBids();return false;"/>
						</div>
					</div>
				</form>
			</div>

			<div class="w3-container">
				<canvas id="chartContainer" style="width:100%;"></canvas>
			</div>

			<table  id='data' style='width: 40%;'
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
						<th class='labelcell' data-sortable='true'>Ημερομηνία</th>
						<th class='labelcell' data-sortable='true'>Πλήθος<br>προσφορών</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
<?php
		}	// Εάν ο χρήστης δεν είναι συνδεδεμένος
		else
		{ 
?>
			<script>
				alert("Για να δείτε το πλήθος των ημερήσιων προσφορών, πρέπει να συνδεθείτε ως Διαχειριστής.");
			</script>
<?php 
		}
?>
	<!-- Γραμμή υποσέλιδου -->
	<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px">
		<p class="w3-right"><?php echo FOOTER_TEXT; ?></p>
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

	<script src="js/jquery-3.3.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap-table.min.js"></script>	

</body>
</html>
