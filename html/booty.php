<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('booty');
// Load the HTML_QuickForm module
require 'HTML/QuickForm.php';
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pfid = $_SESSION['pfid'];
$pfname = get_pf_name($pfid);
$pf_working_date = get_pf_working_date($pfid);
$pf_exch = get_pf_exch($pfid);

