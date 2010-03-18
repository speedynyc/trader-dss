<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('docs');
// Load the HTML_QuickForm module
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pf_id = $_SESSION['pfid'];

function describe_table($table)
{
    global $db_hostname, $db_database, $db_user, $db_password;
    $col_header_colour = '#3BB9FF';
    $header_colour = '#357EC7';
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select table_name, column_name, data_type, comment from view_table_details where table_name = '$table' order by column_name";
    print "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" ><tr><td bgcolor=\"$header_colour\" colspan=\"20\" align=\"center\">$table</td></tr>\n";
    print "<tr><td bgcolor=\"$col_header_colour\">Column Name</td><td bgcolor=\"$col_header_colour\">Data Type</td><td bgcolor=\"$col_header_colour\">Description</td></tr>\n";
    foreach ($pdo->query($query) as $row)
    {
        $column = $row['column_name'];
        $data_type = $row['data_type'];
        if ($row['comment'] == '')
        {
            $comment = $column;
        }
        else
        {
            $comment = $row['comment'];
        }
        print "<tr><td>$column</td><td>$data_type</td><td>$comment</td></tr>\n";
    }
    print "</table>";
}

describe_table('indicators');
describe_table('moving_averages');
describe_table('standard_deviations_from_mean');
describe_table('exchange_indicators');
describe_table('gaps');
describe_table('gains');
?>

