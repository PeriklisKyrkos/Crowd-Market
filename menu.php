<!-- Εμφανίζεται σε όλους -->
<a href="index.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Αρχική</a> 
<hr>
 
<?php
	$user = new User();
?>

<!-- Εμφανίζεται σε admin -->
<?php
	$user = new User();
	if ($user->isAdmin())
	{
?>
		<a href="loadcategoriesjson.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Ανέβασμα κατηγοριών</a> 
		<a href="loadproductsjson.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Ανέβασμα προϊόντων</a> 
		<a href="loadpricesjson.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Ανέβασμα τιμών</a> 
		<hr>
		<a href="loadshopsjson.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Ανέβασμα καταστημάτων</a> 
		<hr>
		<p>Στατιστικά</p>
		<a href="monthlybids.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Μηνιαίες προσφορές</a> 
		<a href="leaderboard.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Leaderboard</a> 
		<hr>
		
<?php
	}
?>
<!-- Εμφανίζεται σε administrator -->
<?php
	if ($user->isUser())
	{
?>
		<a href="edituser.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Επεξεργασία προφίλ</a> 
		<a href="likes.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Ιστορικό likes</a> 
		<a href="userachievments.php" onclick="w3_close()" class="w3-bar-item w3-button w3-hover-white menu_plain plain">Σκορ/Tokens</a> 
		<hr>
<?php
	}
?>
<div class="w3-container" id="showcase">

<?php
if (isset($_SESSION['LoggedUsername']))
{
	echo "<p>Συνδεθήκατε ως:<br><strong>".$_SESSION['LoggedUsername']."</strong><br></p>";
?>
	<form name="userlogout" action="logout.php" style="margin-left:auto; margin-right: auto; method="POST">
		<input type="hidden" id="LoggedUserID" value="<?php echo $_SESSION['LoggedUserID']; ?>"></input>
		<input type="hidden" id="LoggedUserRole" value="<?php echo $_SESSION['LoggedUserRole']; ?>"></input>
		<input type="hidden" id="LoggedUserToken" value="<?php echo $_SESSION['LoggedUserToken']; ?>"></input>
		<input type="Submit" class="dbButton" " name="submit" value="Αποσύνδεση" style="margin-left: auto;margin-right:auto;"></input>
	</form>
<?php
}
?>
</div>
