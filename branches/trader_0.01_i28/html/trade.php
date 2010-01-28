<?php
include("checks.php");
redirect_login_pf();
draw_trader_header('trade');
// Load the HTML_QuickForm module
require 'HTML/QuickForm.php';
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pfid = $_SESSION['pfid'];
$pfname = get_pf_name($pfid);
$pf_working_date = get_pf_working_date($pfid);
$pf_exch = get_pf_exch($pfid);

print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
print "<tr><td>Symb</td><td>Name</td><td>Comment</td><td>Volume</td><td>Value</td></tr>";
try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}
$query = "select * from cart where date <= '$pf_working_date' and pfid = '$pfid';";
foreach ($pdo->query($query) as $row)
{
    $symb_name = get_symb_name($row['symb'], $pf_exch);
    $close = get_stock_close($row['symb'], $pf_working_date, $pf_exch);
    $value = round($close*$row['volume'], 2);
    print '<tr><td>' . $row['symb'] . "</td><td>$symb_name</td><td>" . $row['comment'] . '</td><td>' . $row['volume'] . "</td><td>$value</td></tr>\n";
}
