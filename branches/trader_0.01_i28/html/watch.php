<?php
include("checks.php");
redirect_login_pf();
draw_trader_header('watch');
// Load the HTML_QuickForm module
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pfid = $_SESSION['pfid'];
$pfname = get_pf_name($pfid);
$pf_working_date = get_pf_working_date($pfid);
$pf_exch = get_pf_exch($pfid);

try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function draw_table($pfid, $pf_working_date, $pf_exch, $pf_nam)
{
    global $pdo;
    print '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="watch" id="watch">';
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print "<tr><td>Symb</td><td>Name</td><td>Comment</td><td>Volume</td><td>Value</td></tr>";
    $query = "select * from watch where date <= '$pf_working_date' and pfid = '$pfid' order by symb;";
    foreach ($pdo->query($query) as $row)
    {
        $symb = $row['symb'];
        $symb_name = get_symb_name($symb, $pf_exch);
        $close = get_stock_close($symb, $pf_working_date, $pf_exch);
        $value = round($close*$row['volume'], 2);
        print "<tr><td><input type=\"checkbox\" name=\"mark[]\" value=\"$symb\">$symb</td>\n";
        print "<td>$symb_name</td>\n";
        print "<td><textarea wrap=\"soft\" rows=\"1\" cols=\"50\" name=\"buy_comment_$symb\">" . $row['comment'] . '</textarea></td>';
        print "<td><textarea wrap=\"soft\" rows=\"1\" cols=\"10\" name=\"buy_volume_$symb\">" . $row['volume'] . '</textarea></td>';
        print "<td>$value</td></tr>\n";
    }
    print '<tr><td colspan="10"><input name="recalc" value="Update" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="delete" value="Delete" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="cart" value="Move to Shopping cart" type="submit"/></td></tr>';
    print '</table>';
    print '</form>';
}

#if (isset($_POST['recalc']))
#{
    update_cart('watch', $pfid, $pf_working_date);
#}
#elseif(isset($_POST['delete']))
if(isset($_POST['delete']))
{
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $symb)
        {
            del_from_cart('cart', $symb);
        }
    }
}
elseif(isset($_POST['cart']))
{
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $symb)
        {
            if (! is_in_cart('cart', $symb))
            {
                add_to_cart('cart', $symb, $_POST["buy_comment_$symb"], $_POST["buy_volume_$symb"]);
                del_from_cart('watch', $symb);
            }
        }
    }
}


draw_table($pfid, $pf_working_date, $pf_exch, $pf_name);
