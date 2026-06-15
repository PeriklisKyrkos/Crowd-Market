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
	
		var urlParams = new URLSearchParams(window.location.search);
		var storeID = urlParams.get('sid');
		
		function setLike(e)
		{
			var bid_id = e.getAttribute("bid_id");
			var store_id = e.getAttribute("store_id");
			var prod_id = e.getAttribute("prod_id");
			var bid_user_id = e.getAttribute("bid_user_id");
			var user_id = document.getElementById("LoggedUserID").value;
			
            var creds = "bid_id="+bid_id+"&store_id="+store_id+"&prod_id="+prod_id+"&bid_user_id="+bid_user_id+"&user_id="+user_id;
			
			var obj;
			var response;
            var ajx = new XMLHttpRequest();
			
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () 
			{
                if (ajx.readyState == 4 && ajx.status == 200) 
				{
					response = ajx.responseText;
					response = response.trim(); 
					var data;
					try 
					{
						obj = JSON.parse(response);
						data = obj.rest_dbErrorCode;
					}
					catch
					{
						console.log("Error");
					}
					if ((typeof data !== 'undefined') && (data !== null)) 
					{
						if (data==1062)
							alert("Έχετε ήδη κάνει like σε αυτή την προσφορά.");
					}
					location.reload();
				}
			}
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=set_like", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
		}

		function setDislike(e)
		{
			var bid_id = e.getAttribute("bid_id");
			var store_id = e.getAttribute("store_id");
			var prod_id = e.getAttribute("prod_id");
			var bid_user_id = e.getAttribute("bid_user_id");
			var user_id = document.getElementById("LoggedUserID").value;
			
            var creds = "bid_id="+bid_id+"&store_id="+store_id+"&prod_id="+prod_id+"&bid_user_id="+bid_user_id+"&user_id="+user_id;

			var obj;
			var response;
            var ajx = new XMLHttpRequest();
			
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () 
			{
                if (ajx.readyState == 4 && ajx.status == 200) 
				{
					response = ajx.responseText;
					response = response.trim(); 
					var data;
					try 
					{
						obj = JSON.parse(response);
						data = obj.rest_dbErrorCode;
					}
					catch
					{
						console.log("Error");
					}
					
					if ((typeof data !== 'undefined') && (data !== null)) 
					{
						if (data==1062)
							alert("Έχετε ήδη κάνει like σε αυτή την προσφορά.");
					}
					location.reload();
				}
			}
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=set_dislike", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
		}

		function toggleAvailability(e)
		{
			var bid_id = e.getAttribute("bid_id");
			var store_id = e.getAttribute("store_id");
			var prod_id = e.getAttribute("prod_id");
			var user_id = e.getAttribute("user_id");
			var bid_user_id = e.getAttribute("bid_user_id");

            var creds = "bid_id="+bid_id+"&store_id="+store_id+"&prod_id="+prod_id+"&user_id="+user_id+"&bid_user_id="+bid_user_id;
			var obj;
			var response;
            var ajx = new XMLHttpRequest();
			
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () 
			{
                if (ajx.readyState == 4 && ajx.status == 200) 
				{
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					
					var data = obj.rest_dbErrorCode;
					if ((typeof data !== 'undefined') && (data !== null)) 
					{
						if (data == 0)
							alert("Η διαθεσιμότητα της προσφοράς άλλαξε.");
							location.reload();
					}
				}
			}
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=toggle_availability", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
		}
		
		function getBidsByStore(storeID) 
		{
			var usr_id = document.getElementById("LoggedUserID").value;	

            var creds = "st_id="+storeID;
			var obj;
			var response;
            var ajx = new XMLHttpRequest();
			var activelikes = "";
			
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () 
			{
                if (ajx.readyState == 4 && ajx.status == 200) 
				{
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					
					var data = obj.rest_dbResult;
					if ((typeof data !== 'undefined') && (data !== null)) 
					{
						for (let i = 0; i < data.length; i++) 
						{
							tablerow = "<tr>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].bid_date + "</td>";
							tablerow = tablerow + "<td class='datacell'><img src='images/" + data[i].prod_photo + "' class='thumbnail' /></td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].prod_name + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].bid_price + "</td>";
							if (data[i].in_stock == 'Σε απόθεμα')
							{
								tablerow = tablerow + "<td class='datacell'>" + data[i].likes + "<img src='images/like.png' class='small-icon' style='margin-left: 12px;cursor: pointer;' " +
													"bid_id='" + data[i].bid_id + "' store_id='" + data[i].st_id + "' prod_id='" + data[i].prod_id + "' user_id='" + usr_id + "' bid_user_id='" + data[i].bid_user + "'  onclick='setLike(this);'/></td>";

								tablerow = tablerow + "<td class='datacell'>" + data[i].dislikes + "<img src='images/dislike.png' class='small-icon' style='margin-left: 12px;cursor: pointer;' " + 
													"bid_id='" + data[i].bid_id + "' store_id='" + data[i].st_id + "' prod_id='" + data[i].prod_id + 
													"' user_id='" + usr_id + "' bid_user_id='" + data[i].bid_user +"' onclick='setDislike(this);'/></td>";
							}
							else
							{
								tablerow = tablerow + "<td class='datacell'>" + data[i].likes + "<img src='images/like.png' class='small-icon' style='margin-left: 12px;-webkit-filter: grayscale(100%);' " +
													"bid_id='" + data[i].bid_id + "' store_id='" + data[i].st_id + "' prod_id='" + data[i].prod_id + "' user_id='" + usr_id + "' bid_user_id='" + data[i].bid_user +"' /></td>";
								tablerow = tablerow + "<td class='datacell'>" + data[i].likes + "<img src='images/dislike.png' class='small-icon' style='margin-left: 12px;-webkit-filter: grayscale(100%);' " +
													"bid_id='" + data[i].bid_id + "' store_id='" + data[i].st_id + "' prod_id='" + data[i].prod_id + "' user_id='" + usr_id + "' bid_user_id='" + data[i].bid_user + "' /></td>";
							}
							tablerow = tablerow + "<td class='datacell'><img src='images/" + data[i].in_stock_icon + "' class='small-icon' style='margin-right: 12px;cursor: pointer;' " + 
												"bid_id='" + data[i].bid_id + "' store_id='" + data[i].st_id + "' prod_id='" + data[i].prod_id + "' user_id='" + usr_id + "' bid_user_id='" + data[i].bid_user +"' onclick='toggleAvailability(this);'/> " + data[i].in_stock + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].Username + "</td>";
							tablerow = tablerow + "<td class='datacell'>" + data[i].Score + "</td>";
							tablerow = tablerow + "</tr>";
						}
						var tbodyRef = document.getElementById('data').getElementsByTagName('tbody')[0];
						tbodyRef.innerHTML = tablerow;
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_store_bids", true);
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
			<h1 class="w3-xxxlarge w3-text-red"><?php echo "Αξιολόγηση προσφορών"; ?></h1>
			<hr class="redhr w3-round">
		</div>

<?php
		$user = new User();
		// Εάν ο χρήστης δεν είναι ήδη συνδεδεμένος, 
		// τότε μπορεί να χρησιμοποιήσει τη φόρμα εγγραφής
		if ($user->IsLogged())
		{
?>
			<table  id='data' style='width: 100%;'
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
			  style = 'height: 100vh'
			  >
				<thead>
					<tr>
						<th class='labelcell' data-sortable='true'>Ημερομηνία</th>
						<th class='labelcell' data-sortable='true'></th>
						<th class='labelcell' data-sortable='true'>Προϊόν</th>
						<th class='labelcell' data-sortable='true'>Τιμή</th>
						<th class='labelcell' data-sortable='true'>Like</th>
						<th class='labelcell' data-sortable='true'>Dislike</th>
						<th class='labelcell' data-sortable='true'>Απόθεμα</th>
						<th class='labelcell' data-sortable='true'>Χρήστης</th>
						<th class='labelcell' data-sortable='true'>Σκορ<br>χρήστη</th>
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
	
		getBidsByStore(storeID);
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
