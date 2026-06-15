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
	<script src="js/leaflet.js"></script>
	<script src="js/jquery-3.3.1.min.js"></script>
	<script src="js/crowdmarket.js"></script>
	<link rel="stylesheet" href="styles/crowdmarket.css"/>
	
	<script>
		// Global variables
		var demo = true;

		var map;
		var user_lat; // = 38.2080319;
		var user_lon; // = 21.7126540;

		var mapMarkers = [];
		var selected_cat;

		var userIcon = L.icon({
				iconUrl: 'images/marker.png',
				iconSize: [30, 40], // size of the icon
				iconAnchor: [5, 5], // point of the icon which will correspond to marker's location
				popupAnchor: [0, -5] // point from which the popup should open relative to the iconAnchor
			});

		var cartIcon = L.icon({
				iconUrl: 'images/cart-marker.png',
				iconSize: [30, 30], // size of the icon
				iconAnchor: [5, 5], // point of the icon which will correspond to marker's location
				popupAnchor: [0, -5] // point from which the popup should open relative to the iconAnchor
			});

		var cartIconBid = L.icon({
				iconUrl: 'images/cart-marker-bid.png',
				iconSize: [30, 30], // size of the icon
				iconAnchor: [5, 5], // point of the icon which will correspond to marker's location
				popupAnchor: [0, -5] // point from which the popup should open relative to the iconAnchor
			});


		// Φορτώνει το χάρτη και σημειώνει με μπλε marker την τρέχουσα θέση του χρήστη.
		function getLocation() {
		  if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(showPosition);
		  } else {
			alert ("Geolocation is not supported by this browser.");
		  }
		}

		function showPosition(position) 
		{	
			if (demo) 
			{
				user_lat = 38.2080319;
				user_lon = 21.7126540;
			}
			else
			{
				user_lat = position.coords.latitude;
				user_lon = position.coords.longitude;
			}

			try
			{
				map.remove();
			}
			catch(err) 
			{
				console.log(err.message);
			}
			map = L.map('map').setView([user_lat, user_lon], 13);
			L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', 
			{
				maxZoom: 19,
				attribution: '© OpenStreetMap'
			}).addTo(map);
			
			var marker = L.marker([parseFloat(user_lat), parseFloat(user_lon)], {icon: userIcon}).addTo(map);
			mapMarkers.push(marker);
			map.addLayer(marker);
			fillStoresList("", user_lat, user_lon);

		}

		// Βρίσκει την τρέχουσα θέση του χρήστη και εμφανίζει τα καταστήματα με προσφορές
		// που βρίσκονται σε απόσταση 6χλμ γύρω του.
		function showStoresWithBidsOnMap() {
		  if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(_showStoresWithBidsOnMap);
		  } else {
			alert ("Geolocation is not supported by this browser.");
		  }
		}

		function _showStoresWithBidsOnMap(position) {
			
			if (demo) 
			{
				user_lat = 38.2080319;
				user_lon = 21.7126540;
			}
			else
			{
				user_lat = position.coords.latitude;
				user_lon = position.coords.longitude;
			}
			try
			{
				map.remove();
			}
			catch(err) 
			{
				console.log(err.message);
			}
			map = L.map('map').setView([user_lat, user_lon], 13);
			L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', 
			{
				maxZoom: 19,
				attribution: '© OpenStreetMap'
			}).addTo(map);

			showStoresWithBids(user_lat, user_lon, map);

			var marker = L.marker([parseFloat(user_lat), parseFloat(user_lon)], {icon: userIcon}).addTo(map);
			mapMarkers.push(marker);
			map.addLayer(marker);
		}

		// Βρίσκει την τρέχουσα θέση του χρήστη και εμφανίζει τα καταστήματα
		// με το όνομα που έχει επιλέξει στο φίλτρο καταστήματος
		function showStoresByNameOnMap() {
		  if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(_showStoresByNameOnMap);
		  } else {
			alert ("Geolocation is not supported by this browser.");
		  }
		}

		function _showStoresByNameOnMap(position) {
			
			if (demo) 
			{
				user_lat = 38.2080319;
				user_lon = 21.7126540;
			}
			else
			{
				user_lat = position.coords.latitude;
				user_lon = position.coords.longitude;
			}
			try
			{
				map.remove();
			}
			catch(err) 
			{
				console.log(err.message);
			}
			map = L.map('map').setView([user_lat, user_lon], 13);
			L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', 
			{
				maxZoom: 19,
				attribution: '© OpenStreetMap'
			}).addTo(map);
			
			var selected_store = document.getElementById('selected-store').value;
			showStoresByName(selected_store, user_lat, user_lon, map);

			var marker = L.marker([parseFloat(user_lat), parseFloat(user_lon)], {icon: userIcon}).addTo(map);
			mapMarkers.push(marker);
			map.addLayer(marker);
		}

		// Βρίσκει την τρέχουσα θέση του χρήστη και εμφανίζει τα καταστήματα
		// με το όνομα που έχει επιλέξει στο φίλτρο καταστήματος
		function showStoresForCategoryOnMap() {
		  if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(_showStoresForCategoryOnMap);
		  } else {
			alert ("Geolocation is not supported by this browser.");
		  }
		}

		function _showStoresForCategoryOnMap(position) {
			
			if (demo) 
			{
				user_lat = 38.2080319;
				user_lon = 21.7126540;
			}
			else
			{
				user_lat = position.coords.latitude;
				user_lon = position.coords.longitude;
			}
			try
			{
				map.remove();
			}
			catch(err) 
			{
				console.log(err.message);
			}
			map = L.map('map').setView([user_lat, user_lon], 13);
			L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', 
			{
				maxZoom: 19,
				attribution: '© OpenStreetMap'
			}).addTo(map);
			
			var selected_cat = document.getElementById('selected-cat').value;
			showStoresForCategory(selected_cat, user_lat, user_lon, map);

			var marker = L.marker([parseFloat(user_lat), parseFloat(user_lon)], {icon: userIcon}).addTo(map);
			mapMarkers.push(marker);
			map.addLayer(marker);
		}


		/* *****************************************************************************
		
				ΥΛΟΠΟΙΗΣΗ ΤΩΝ FUNCTIONS
		
		   *****************************************************************************
		*/
		
		/*  H function φορτώνει στο χάρτη μόνο τα markers των καταστημάτων
			που έχουν ενεργές προσφορές.
			Απαντά στο ερώτημα 2α
		*/
		function showStoresWithBids(current_lat, current_lon, tomap) 
		{
			const usr_token=document.getElementById("LoggedUserToken").value;
            var creds = "current_lat="+current_lat+"&current_lon="+current_lon+"&usr_token="+usr_token;
			
			var obj;
			var response;
			var isAdmin = document.getElementById("LoggedUserRole").value;
			
			for(var i = 0; i < this.mapMarkers.length; i++){
				tomap.removeLayer(this.mapMarkers[i]);
			}
            var ajx = new XMLHttpRequest();
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () 
			{
                if (ajx.readyState == 4 && ajx.status == 200) 
				{
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					var data = obj.rest_dbResult;

					if ((typeof data !== 'undefined') & (data !== null)) 
					{
						var tempStoreID = "";
						
						for (let i = 0; i < data.length; i++) 
						{	
							let popupInfo = "";
							if (tempStoreID != data[i].st_id)
							{
								tempStoreID = data[i].st_id;
								var marker = L.marker([parseFloat(data[i].st_lat), parseFloat(data[i].st_lon)], {icon: cartIconBid}).addTo(tomap);
								var distance = data[i].distance;
								var urlBid = "setbid.php?sid=" + data[i].st_id;
								var urlAssess = "assessbid.php?sid=" + data[i].st_id;
								var urlDelete = "deletebid.php?sid=" + data[i].st_id;
								popupInfo = '<p><strong>' + data[i].st_name+ '</strong></p>'+
											data[i].st_street + " " + data[i].st_housenumber + " " + data[i].st_postcode + " " + data[i].st_city
								if ((demo) || (distance<=50))
								{
									popupInfo=popupInfo+"<input type='submit' class='dbButton' name='Submit' value='Νέα προσφορά' onclick='loadPage(\""+urlBid+"\");'/>";
									popupInfo=popupInfo+"<input type='submit' class='dbButton' name='SubmitAssess' value='Αξιολόγηση' onclick='loadPage(\""+urlAssess+"\");'/>";
								}
								if (isAdmin==1)
									popupInfo = popupInfo + "<input type='submit' class='dbButton' name='deleteBid' value='Διαγραφή' onclick='loadPage(\""+urlDelete+"\");'/>";
								for (let j=0; j < data.length; j++)
								{
									if (data[j].st_id == tempStoreID)
									{
										popupInfo = popupInfo + "<hr>" + data[j].prod_name + "<span style='margin-right: 10px;'></span><strong>€"+ data[j].bid_price + "</strong><br>" +
												"<img src='images/like.png' class='very-small-icon' />" + data[j].likes +
												"<img src='images/dislike.png' class='very-small-icon' style='margin-left: 20px;'/>" + data[j].dislikes + "<br><br>" +
												"<img src='images/" + data[j].in_stock_icon + "' class='very-small-icon' style='margin-right: 10px;'/>" + data[j].in_stock;
									}
								}
								marker.bindPopup(popupInfo);
								
								mapMarkers.push(marker);
								tomap.addLayer(marker);							

							}
						}
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_stores_with_bid", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
        }		
		
		
		// Εμφανίζει markers όλων των καταστημάτων που ταιριάζουν στο φίλτρο καταστημάτων, ανεξάρτητα με το αν αυτά έχουν προσφορά.
		// Αυτά που έχουν προσφορά ή προσφορές εμφανίζονται με διαφορετικό marker.
		function showStoresByName(selected_store, current_lat, current_lon, tomap) 
		{
			const usr_token=document.getElementById("LoggedUserToken").value;
            var creds = "st_name="+selected_store+"&current_lat="+current_lat+"&current_lon="+current_lon+"&usr_token="+usr_token;
			var obj;
			var response;
			var marker;
			var popupInfo = "";
			var isAdmin = document.getElementById("LoggedUserRole").value;
			
			for (var i = 0; i < this.mapMarkers.length; i++)
			{
				tomap.removeLayer(this.mapMarkers[i]);
			}
            var ajx = new XMLHttpRequest();
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () {
                if (ajx.readyState == 4 && ajx.status == 200) {
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					var data = obj.rest_dbResult;

					// Καθάρισμα των προηγούμενων στοιχείων της λίστας καταστημάτων
					if ((typeof data !== 'undefined') & (data !== null)) 
					{
						var tempStoreID = "";
						for (let i = 0; i < data.length; i++) 
						{	
							if (tempStoreID != data[i].st_id)
							{
								tempStoreID = data[i].st_id;
								if (data[i].has_bid == 0)
								{
									marker = L.marker([parseFloat(data[i].st_lat), parseFloat(data[i].st_lon)], {icon: cartIcon}).addTo(tomap);
									var distance = data[i].distance;
									var urlBid = "setbid.php?sid=" + data[i].st_id;
									popupInfo = '<p><strong>' + data[i].st_name+ '</strong></p>' + data[i].st_street + " " + data[i].st_housenumber + " " + data[i].st_postcode + " " + data[i].st_city;
									if ((demo) || (distance<=50))
									{
										popupInfo=popupInfo+"<input type='submit' class='dbButton' name='Submit' value='Νέα προσφορά' onclick='loadPage(\""+urlBid+"\");'/>";
									}
								}
								else
								{
									marker = L.marker([parseFloat(data[i].st_lat), parseFloat(data[i].st_lon)], {icon: cartIconBid}).addTo(tomap);
									var distance = data[i].distance;
									var urlBid = "setbid.php?sid=" + data[i].st_id;
									var urlAssess = "assessbid.php?sid=" + data[i].st_id;
									var urlDelete = "deletebid.php?sid=" + data[i].st_id;
									popupInfo = '<p><strong>' + data[i].st_name+ '</strong></p>'+
												data[i].st_street + " " + data[i].st_housenumber + " " + data[i].st_postcode + " " + data[i].st_city;
									if ((demo) || (distance<=50))
									{
										popupInfo=popupInfo+"<input type='submit' class='dbButton' name='Submit' value='Νέα προσφορά' onclick='loadPage(\""+urlBid+"\");'/>";
										popupInfo=popupInfo+"<input type='submit' class='dbButton' name='SubmitAssess' value='Αξιολόγηση' onclick='loadPage(\""+urlAssess+"\");'/>";
									}
									if (isAdmin==1)
										popupInfo = popupInfo + "<input type='submit' class='dbButton' name='deleteBid' value='Διαγραφή' onclick='loadPage(\""+urlDelete+"\");'/>";

									for (let j=0; j < data.length; j++)
									{
										if (data[j].st_id == tempStoreID)
										{
											if (data[j].prod_name !== null)
											{
												popupInfo = popupInfo + "<hr>" + data[j].prod_name + "<span style='margin-right: 10px;'></span><strong>€"+ data[j].bid_price + "</strong><br>" +
														"<img src='images/like.png' class='very-small-icon' />" + data[j].likes +
														"<img src='images/dislike.png' class='very-small-icon' style='margin-left: 20px;'/>" + data[j].dislikes + "<br><br>" +
														"<img src='images/" + data[j].in_stock_icon + "' class='very-small-icon' style='margin-right: 10px;'/>" + data[j].in_stock;
											}
										}
									}
								}
								marker.bindPopup(popupInfo);
								mapMarkers.push(marker);
								map.addLayer(marker);
							}
						}
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_stores_from_point", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
        }		

		function fillStoresList(selected_store, current_lat, current_lon) 
		{
			const usr_token=document.getElementById("LoggedUserToken").value;
            var creds = "st_name="+selected_store+"&current_lat="+current_lat+"&current_lon="+current_lon+"&usr_token="+usr_token;
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
					var storesList = document.getElementById("stores-filter");
					// Καθάρισμα των προηγούμενων στοιχείων της λίστας καταστημάτων
					var storesListLength = storesList.options.length;
					
					for (let i = 0 ; i< storesListLength; i++)
					{
						storesList.remove(i);
					}

					var option = document.createElement("option");
					option.text = "-- Επιλέξτε ένα κατάστημα --";
					storesList.add(option);

					if ((typeof data !== 'undefined') & (data !== null)) 
					{
						for (let i = 0; i < data.length; i++) 
						{	
							var option = document.createElement("option");
							option.text = data[i].st_name;
							storesList.add(option);
						}
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_store_names_from_point", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
        }		


		function showStoresForCategory(selected_cat, current_lat, current_lon, tomap) 
		{
			const usr_token=document.getElementById("LoggedUserToken").value;
            var creds = "selected_cat="+selected_cat+"&current_lat="+current_lat+"&current_lon="+current_lon+"&usr_token="+usr_token;
			var obj;
			var response;
			var isAdmin = document.getElementById("LoggedUserRole").value;

			var cartIcon = L.icon({
					iconUrl: 'images/cart-marker.png',
					iconSize: [30, 30], // size of the icon
					iconAnchor: [5, 5], // point of the icon which will correspond to marker's location
					popupAnchor: [0, -5] // point from which the popup should open relative to the iconAnchor
				});
			for(var i = 0; i < this.mapMarkers.length; i++){
				tomap.removeLayer(this.mapMarkers[i]);
			}
            var ajx = new XMLHttpRequest();
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () {
                if (ajx.readyState == 4 && ajx.status == 200) {
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);

					var data = obj.rest_dbResult;


					if ((typeof data !== 'undefined') & (data !== null)) 
					{
						var tempStoreID = "";
						let popupInfo = "";
						for (let i = 0; i < data.length; i++) 
						{	
							if (data[i].prod_name !== null)
							{
								if (tempStoreID != data[i].st_id)
								{
									tempStoreID = data[i].st_id;
									var marker = L.marker([parseFloat(data[i].st_lat), parseFloat(data[i].st_lon)], {icon: cartIconBid}).addTo(tomap);
									var distance = data[i].distance;
									var urlBid = "setbid.php?sid=" + data[i].st_id;
									var urlAssess = "assessbid.php?sid=" + data[i].st_id;
									var urlDelete = "deletebid.php?sid=" + data[i].st_id;
									popupInfo = '<p><strong>' + data[i].st_name+ '</strong></p>'+
												data[i].st_street + " " + data[i].st_housenumber + " " + data[i].st_postcode + " " + data[i].st_city;
									if ((demo) || (distance<=50))
									{

										popupInfo=popupInfo+"<input type='submit' class='dbButton' name='Submit' value='Νέα προσφορά' onclick='loadPage(\""+urlBid+"\");'/>";
										popupInfo=popupInfo+"<input type='submit' class='dbButton' name='SubmitAssess' value='Αξιολόγηση' onclick='loadPage(\""+urlAssess+"\");'/>";
									}
									if (isAdmin==1)
										popupInfo = popupInfo + "<input type='submit' class='dbButton' name='deleteBid' value='Διαγραφή' onclick='loadPage(\""+urlDelete+"\");'/>";

									for (let j=0; j < data.length; j++)
									{
										if (data[j].st_id == tempStoreID)
										{
											popupInfo = popupInfo + "<hr>" + data[j].prod_name + "<span style='margin-right: 10px;'></span><strong>€"+ data[j].bid_price + "</strong><br>" +
													"<img src='images/like.png' class='very-small-icon' />" + data[j].likes +
													"<img src='images/dislike.png' class='very-small-icon' style='margin-left: 20px;'/>" + data[j].dislikes + "<br><br>" +
													"<img src='images/" + data[j].in_stock_icon + "' class='very-small-icon' style='margin-right: 10px;'/>" + data[j].in_stock;
										}
									}
									marker.bindPopup(popupInfo);
									
									mapMarkers.push(marker);
									map.addLayer(marker);
								}
							}
						}
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_bids_on_category", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
        }		
		
		async function getProductCategories() 
		{
			const usr_token=document.getElementById("LoggedUserToken").value;
			var creds = "usr_token="+usr_token;
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

					if ((typeof data !== 'undefined') & (data !== null)) 
					{
						var tempCategory = "";
						for (let i = 0; i < data.length; i++) 
						{
							if (tempCategory != data[i].Category)
							{
								$("#all_products").append('<li class="category-box" onclick="document.getElementById(\'selected-cat\').value = this.innerHTML;showStoresForCategoryOnMap();">' + data[i].Category + '</li>');
								tempCategory = data[i].Category;
								
								var tempSubcategory = "";
								for (let j = 0; j < data.length; j++)
								{
									if ((tempSubcategory != data[j].Subcategory) & (data[j].Category == data[i].Category))
									{
										tempSubcategory = data[j].Subcategory;
										
										var tempProduct = "";
										for (let k = 0; k < data.length; k++)
										{
											if ((tempProduct != data[k].Product) & (tempSubcategory == data[k].Subcategory) & (data[k].Category == data[i].Category))
											{
												tempProduct = data[k].Product;												
											}
										}
										
									}
								}
								$("#all_products ul").append("</li>");
							}
						}
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_products", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
			
		}
	
		function getOption(whichList) {
			return document.getElementById(whichList).value;
		}
		
		function setItemValue(whichItem, whichValue) {
			document.getElementById(whichItem).value = whichValue;
		}
		
		function showStoresWithFilter() {
			showStoresByNameOnMap();
		}
		
		function loadPage(url) {
			window.location.href = url;
		};
	</script>
</head>

<body>

<!-- Sidebar/menu -->
<nav class="w3-sidebar w3-red w3-collapse w3-top w3-large w3-padding" style="z-index:3;width:300px;" id="mySidebar"><br>
  <a href="javascript:void(0)" onclick="w3_close()" class="w3-button w3-hide-large w3-display-topleft" style="width:100%;font-size:22px">Close Menu</a>
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
<div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

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
	if (!$user->IsLogged())
	{
?>
		<div class="w3-container" id="showcase">
			<form name="userlogin" method="POST">
				<div class="row">
					<p class="black">Όνομα χρήστη:</p>
					<input type="text" name="username" value="" size="16"/>
				</div>
				<div class="row">
					<p class="black">Συνθηματικό:</p>
					<input type="password" name="password" value="" size="16"/><p></p>
				</div>
				<div class="row">
					<input class="dbButton" type="Submit" name="Submit" value="Login"></input>
				</div>
			</form>
			<div class="row" style="margin-top: 30px;">
				<a href="newuser.php"><img src="<?php echo IMAGE_FOLDER.'registernow.png'; ?>" class="small-icon" /></a>
				<a href="newuser.php" class="black" >Κλικ εδώ εάν δεν έχετε λογαριασμό...</a>
			</div>
		</div>

<?php
		if (isset($_POST['Submit']))
		{
			$postdata = http_build_query(
			array(
					'username' => $_POST['username'], 
					'password' => $_POST['password']
				)
			);
			//var_dump ($postdata);
			$opts = array	('http' =>
								array	(
										'method' => 'POST',
										'header' => 'Content-type: application/x-www-form-urlencoded',
										'content' => $postdata
										)
							);
			$context  = stream_context_create($opts);
			$return_json = "";
			$return_json = file_get_contents(WEB_SERVER.'api.php?action=do_login', false, $context);
			$return_info = json_decode(removeBOM($return_json), true);
			
			$items = sizeof($return_info['rest_dbResult']);
			
			if (isset($return_info['rest_dbResult']))
			{
				if ($items > 0)
				{
					$data = $return_info['rest_dbResult'];
					foreach ($data as $item) 
					{
						$_SESSION['LoggedUserID'] = $item['usr_id'];
						$_SESSION['LoggedUsername'] = $item['usr_username'];
						$_SESSION['LoggedUserRole'] = $item['usr_role'];
						$_SESSION['LoggedUserToken'] = $item['usr_token'];
					}
					header('Location:index.php');
				}
				else
				{
					echo "<h5 style='color: red;'>Η προσπάθεια σύνδεσής σας ήταν αποτυχημένη</h5>";
				}
			}
		}
	}
	else
	{ 
?>
		<div class="w3-main content-div">
			<div class="w3-container">
				<div class="row">
					<div id="map" style="height: 50vh;">
					</div>
				</div>
				<div class="row">
					<div class='w3-half w3-margin-bottom'>
						<h2>Φίλτρο καταστημάτων</h2>
						<div>
							<table>
							<tr>
							<td>
							<input type="text" style="margin-top: 16px; margin-right: 8px; width: 200px" id="selected-store"></input>
							</td>
							<td>
							<img src="<?php echo IMAGE_FOLDER; ?>search.png" class="small-icon" onclick="showStoresWithFilter();"/>
							</td>
							</tr>
							</table>
						</div>
						<select name="stores-filter" id="stores-filter" style="width:260px; margin-left: 2px; margin-top: 25px;" onchange="setItemValue('selected-store', getOption('stores-filter'));">
						</select>
					</div>
					<div class='w3-half w3-margin-bottom'>
						<h2>Φίλτρο προσφορών</h2>
						<input type="hidden" id="selected-cat" />
						<nav class="my-menu">
							<ul class="all_products" id="all_products">
							</ul>
						</nav>
					</div>
				</div>
			</div>
		</div>
		<div class="w3-main content-div">
			<div class="w3-container" id="map-container">
				<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px"><p class="w3-right">
					<p><?php echo FOOTER_TEXT; ?></p>
				</div>
			</div>
		</div>
		<script>

		getLocation();
		getProductCategories();
		showStoresWithBidsOnMap();

		</script>
<?php 
	}
?>

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
