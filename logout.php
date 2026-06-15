<!DOCTYPE html>

<?php 
	session_start();
	require_once "includes/config.php";
	require_once "includes/functions.php";
?>

<html lang="en">
<head>
	<title><?php echo SITE_TITLE; ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="styles/w3.css">
	<link rel="stylesheet" href="styles/crowdmarket.css">
	<style>
		.w3-half img{margin-bottom:-6px;margin-top:16px;opacity:0.8;cursor:pointer}
		.w3-half img:hover{opacity:1}
	</style>
</head>
<body>

<!-- Sidebar/menu -->
<nav class="w3-sidebar w3-red w3-collapse w3-top w3-large w3-padding" style="z-index:3;width:300px;font-weight:bold;" id="mySidebar"><br>
  <a href="javascript:void(0)" onclick="w3_close()" class="w3-button w3-hide-large w3-display-topleft" style="width:100%;font-size:22px">Close Menu</a>
  <div class="w3-container">
    <h3 class="w3-padding-32"><b><?php echo SITE_TITLE; ?></b></h3>
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
<div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

<!-- !PAGE CONTENT! -->
<div class="w3-main" style="margin-left:340px;margin-right:40px">

  <!-- Header -->
  <div class="w3-container" id="showcase">
    <h1 >Αποσύνδεση...</h1>
    <hr class="redhr w3-round">
  </div>
  

  <!-- Αποτέλεσμα login -->
  <div class="w3-container" id="packages" style="margin-top:75px">
	<?php 
		echo "<div class='w3-container' id='packages' style='margin-top:15px'>";
		echo "<div class='DialogBox'>";
		echo "<hr style='width:90%;border:5px solid red' class='w3-round'>";
		echo "Έχετε αποσυνδεθεί από την πλατφόρμα ".$_SESSION['LoggedUsername'];
		session_destroy();
		echo "<br><br><br><a href='index.php'>Συνέχεια στην αρχική σελίδα</a>";
		echo "</div>";
		echo "</div>";
	?>
  </div>


<!-- End page content -->
</div>

<!-- W3.CSS Container -->
<div class="w3-light-grey w3-container w3-padding-32" style="margin-top:75px;padding-right:58px"><p class="w3-right"><?php echo FOOTER_TEXT; ?></p></div>

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
