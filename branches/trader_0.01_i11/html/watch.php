<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('watch');
// Load the HTML_QuickForm module
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pf_id = $_SESSION['pfid'];
$pf_name = get_pf_name($pf_id);
$pf_working_date = get_pf_working_date($pf_id);
$pf_exch = get_pf_exch($pf_id);

try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function draw_table($pf_id, $pf_working_date, $pf_exch, $pf_nam)
{
    global $pdo;
    print '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="watch" id="watch">';
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print '<tr><td>Symb</td><td>Name</td><td>Comment</td><td>Volume</td><td>Value</td>';
    if (isset($_POST['chart']))
    {
        print '<td>Chart</td></tr>';
    }
    print '</tr>';
    $query = "select * from watch where date <= '$pf_working_date' and pfid = '$pf_id' order by symb;";
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
        print "<td>$value</td>\n";
        if (isset($_POST['chart']))
        {
            print "<td><img SRC=\"/cgi-bin/chartstock.php?TickerSymbol=$symb&TimeRange=180&working_date=$pf_working_date&exch=$pf_exch&ChartSize=S&Volume=1&VGrid=1&HGrid=1&LogScale=0&ChartType=OHLC&Band=None&avgType1=SMA&movAvg1=10&avgType2=SMA&movAvg2=25&Indicator1=RSI&Indicator2=MACD&Indicator3=WilliamR&Indicator4=TRIX&Button1=Update%20Chart\" ALIGN=\"bottom\" BORDER=\"0\"></td>";
        }
        print "</tr>\n";
    }
    print '<tr><td colspan="10"><input name="recalc" value="Update" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="delete" value="Delete" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="cart" value="Move to Shopping cart" type="submit"/></td></tr>';
    if (isset($_POST['chart']))
    {
        print "<tr><td colspan=\"10\"><input type=\"checkbox\" name=\"chart\" value=\"chart\" checked>Draw Charts</td>\n";
    }
    else
    {
        print "<tr><td colspan=\"10\"><input type=\"checkbox\" name=\"chart\" value=\"chart\">Draw Charts</td>\n";
    }
    print '</table>';
    print '</form>';
}

#if (isset($_POST['recalc']))
#{
    update_cart('watch', $pf_id, $pf_working_date);
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
                if (add_to_cart('cart', $symb, $_POST["buy_comment_$symb"], $_POST["buy_volume_$symb"]))
                {
                    del_from_cart('watch', $symb);
                }
            }
        }
    }
}


draw_table($pf_id, $pf_working_date, $pf_exch, $pf_name);
