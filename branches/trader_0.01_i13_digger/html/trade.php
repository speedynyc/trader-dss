<?php
@include("checks.php");
redirect_login_pf();
// Load the HTML_QuickForm module
$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pfid = $_SESSION['pfid'];
print "OK $username ($uid), Lets get to trading portfolio $pfid!\n";
?>
