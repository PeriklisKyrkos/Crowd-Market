<!DOCTYPE html>
<?php 
	ob_start();
	session_start();
	require_once "includes/config.php";
	include "includes/functions.php";
	$store_id = $_GET['sid'];
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
	<link rel="stylesheet" href="styles/crowdmarket.css"/>
	<link rel="stylesheet" href="styles/accordion.css"/>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
	  <script>
  window.console = window.console || function(t) {};
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
		<h1 class="w3-xxxlarge w3-text-red"><?php echo "ΝΕΑ ΠΡΟΣΦΟΡΑ"; ?></h1>
		<hr class="redhr w3-round">
	</div>
	<div class="w3-container" id="showcase">

		<div class='w3-row-padding'>
			<div id="bid-form" style="display: none;">
				<div class='w3-row-padding'>
					<div class="col-25 label-column">
						<span class="black">Επιλέξατε: </span>
					</div>
					<div class="col-75 data-column">
						<input type="text" readonly name="selected_product" id="selected-product" size="60" />
						<input type="hidden" readonly name="selected_product_id" id="selected-product-id" size="10" />
					</div>			
				</div>
				<div class='w3-row-padding'>
					<div class="col-25 label-column">
						<span class="black">Τιμή προσφοράς: </span>
					</div>
					<div class="col-75 data-column">
						<input type="number" required name="selected_price" id="selected-price" min="0" value="" step=".01" name="prod_price" id="prod_price" />
						<input type="hidden" value="<?php echo $store_id; ?>" id="selected-store" />
						<input type="submit" class="dbButton" name="Submit" value="Καταχώρηση" onclick="insertBid();return false;"/>
					</div>
				</div>
			</div>
		</div>			
		<div class='w3-row-padding'>
			<div class='w3-half w3-margin-bottom'>
				<div id="accordiondiv">
					<aside class="accordion" id="accordion">
					</aside>
				</div>
			</div>
			<div class='w3-half w3-margin-bottom'>
				<h4>Αναζήτηση με ονομασία προϊόντος</h4>
				<div>
					<table>
					<tr>
					<td>
					<input type="text" style="margin-top: 16px; margin-right: 8px;" id="search-product"></input>
					</td>
					<td>
					<img src="<?php echo IMAGE_FOLDER; ?>search.png" class="small-icon" onclick="showProductsWithFilter();"/>
					</td>
					</tr>
					</table>
				</div>
				<div id="products-filter" style="width: 100%; margin-left: 2px;">
				</div>
			</div>
		</div>
		<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px"><p class="w3-right">
			<p><?php echo FOOTER_TEXT; ?></p>
		</div>
	</div>

	<script>
	
		function showProductsByName(selected_product) 
		{
            var creds = "prod_name="+selected_product;
			var obj;
			var response;

            var ajx = new XMLHttpRequest();
			ajx.responseType = 'text';
            ajx.onreadystatechange = function () {
                if (ajx.readyState == 4 && ajx.status == 200) 
				{
					response = ajx.responseText;
					response = response.trim(); 
					obj = JSON.parse(response);
					
					productsList = "";
					var data = obj.rest_dbResult;
				}
				if ((typeof data !== 'undefined') & (data !== null)) 
				{
					for (let i = 0; i < data.length; i++) 
					{	
						productsList = productsList + "<h3 class='item' id='product' prod_id='" + data[i].prod_id + "'onclick='selectProduct(this);'>" + data[i].prod_name + "</h3>";
					}
					var productsContainer = document.getElementById('products-filter');
					productsContainer.innerHTML = productsList;
				}
			}
            
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_products_by_name", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
        }		
	
		function getProducts() 
		{
			var creds = "";
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
						accordion = document.getElementById("accordion");
						const container = document.getElementById("accordiondiv");
						//container.appendChild(accordion);
						
						var tempCategory = "";
						for (let i = 0; i < data.length; i++) 
						{
							if (tempCategory != data[i].Category)
							{
								tempCategory = data[i].Category;

								const level1 = document.createElement("h1");
								const node1 = document.createTextNode(data[i].Category);
								level1.appendChild(node1);

								accordion.appendChild(level1);

								const opening1 = document.createElement("div");
								opening1.classList.add('opened-for-codepen');
								
								accordion.appendChild(opening1);
																
								var tempSubcategory = "";
								for (let j = 0; j < data.length; j++)
								{
									if ((tempSubcategory != data[j].Subcategory) & (data[j].Category == data[i].Category))
									{
										tempSubcategory = data[j].Subcategory;
										
										const level2 = document.createElement("h2");
										const node2 = document.createTextNode(data[j].Subcategory);
										level2.appendChild(node2);
										
										opening1.appendChild(level2);

										const opening2 = document.createElement("div");
										opening2.classList.add('opened-for-codepen');
										opening1.appendChild(opening2);

										var tempProduct = "";
										for (let k = 0; k < data.length; k++)
										{
											if ((tempProduct != data[k].Product) & (tempSubcategory == data[k].Subcategory) & (data[k].Category == data[i].Category))
											{
												tempProduct = data[k].Product;												

												const level3 = document.createElement("h3");
												const node3 = document.createTextNode(data[k].Product);
												level3.appendChild(node3);
												console.log(data[k].prod_id);
												level3.setAttribute("id", "product");
												level3.setAttribute("prod_id", data[k].prod_id);
												level3.onclick = function() {selectProduct(this); };
												
												opening2.appendChild(level3);

												//const opening3 = document.createElement("div");
												//opening3.classList.add('opened-for-codepen');
												//opening2.appendChild(opening3);

											}
										}
									}
								}
							}
						}
					}
                }
            };
            ajx.open("POST", "http://localhost/crowdmarket/api.php?action=get_products", true);
            ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajx.send(creds);
		}
		
		function selectProduct(e)
		{
			var id = e.id.toUpperCase();
			
			var selected_prod_id = e.getAttribute("prod_id");
			
			if (id == 'PRODUCT')
			{
				var bidForm = document.getElementById("bid-form");
				bidForm.style.display = "block";
				
				var selectedProduct = document.getElementById("selected-product");
				selectedProduct.value = e.innerHTML;

				var selectedProductID = document.getElementById("selected-product-id");
				selectedProductID.value = selected_prod_id;
			}
		}
		
		function showProductsWithFilter() {
			var selected_product = document.getElementById('search-product').value;
			
			showProductsByName(selected_product);
		}

		function insertBid() {
            var bid_product = document.getElementById("selected-product-id").value;
            var bid_price = document.getElementById("selected-price").value;
			var bid_store = document.getElementById("selected-store").value;
			var bid_user = document.getElementById("LoggedUserID").value;

			if ((bid_price.length > 0) && (bid_price > 0)) 
			{
				var creds = "bid_product="+bid_product+"&bid_price="+bid_price+"&bid_store="+bid_store+"&bid_user="+bid_user;
				var obj;
				var response;
				
				var ajx = new XMLHttpRequest();
				ajx.responseType = 'text';
				ajx.onreadystatechange = function () {
					if (ajx.readyState == 4 && ajx.status == 200) {
						response = ajx.responseText;
						response = response.trim(); 
						alert(response);
						obj = JSON.parse(response);
						
						var data = obj.rest_dbResult;
					}
				};
				ajx.open("POST", "http://localhost/crowdmarket/api.php?action=set_new_bid", true);
				ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				ajx.send(creds);
			}
			else
			{
				alert("Πρέπει να εισάγετε την τιμή της προσφοράς που βρήκατε για να μπορέσει να γίνει η υποβολή");
			}
        }		
		
	</script>
	
	
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

	getProducts();
</script>

<script id="rendered-js" >
	var headers = ["H1", "H2", "H3", "H4", "H5", "H6"];

	$(".accordion").click(function (e) {
		var target = e.target, name = target.nodeName.toUpperCase();

		if ($.inArray(name, headers) > -1) 
		{
			var subItem = $(target).next();

			//slideUp all elements (except target) at current depth or greater
			var depth = $(subItem).parents().length;
			var allAtDepth = $(".accordion p, .accordion div").filter(function () 
			{
				if ($(this).parents().length >= depth && this !== subItem.get(0)) 
				{
					return true;
				}
			});
			$(allAtDepth).slideUp("fast");

			//slideToggle target content and adjust bottom border if necessary
			subItem.slideToggle("fast", function () {
				$(".accordion :visible:last").css("border-radius", "0 0 10px 10px");
			});
			$(target).css({ "border-bottom-right-radius": "0", "border-bottom-left-radius": "0" });
		}
	});

	</script>
</body>
</html>