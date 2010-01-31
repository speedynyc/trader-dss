<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('booty');
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
    print '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="cart" id="cart">';
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print '<tr><td>Symb</td><td>Name</td><td>Comment</td><td>Volume</td><td>Buy Price</td><td>close</td><td>gain</td><td>Value</td>';
    if (isset($_POST['chart']))
    {
        print '<td>Chart</td></tr>';
    }
    print '</tr>';
    $query = "select * from trades where pfid = '$pf_id' order by symb;";
    foreach ($pdo->query($query) as $row)
    {
        $symb = $row['symb'];
        $symb_name = get_symb_name($symb, $pf_exch);
        $price = $row['price'];
        $close = get_stock_close($symb, $pf_working_date, $pf_exch);
        $price_diff_pc = 100 - round(($price/$close)*100, 2);
        $value = round($close*$row['volume'], 2);
        if ($price_diff_pc == 0)
        {
            $colour = 'black';
        }
        elseif ($price_diff_pc > 0)
        {
            $colour = 'green';
        }
        else
        {
            $colour = 'red';
        }
        print "<tr><td><input type=\"checkbox\" name=\"mark[]\" value=\"$symb\"><font color=\"$colour\">$symb</font></td>\n";
        print "<td><font color=\"$colour\">$symb_name</font></td>\n";
        print "<td><textarea wrap=\"soft\" rows=\"1\" cols=\"50\" name=\"buy_comment_$symb\">" . $row['comment'] . '</textarea></td>';
        print "<td>" . $row['volume'] . '</td>';
        print "<td>$price</td>\n";
        print "<td>$close</td>\n";
        print "<td>$price_diff_pc %</td>\n";
        print "<td>$value</td>\n";
        if (isset($_POST['chart']))
        {
            print "<td><img SRC=\"/cgi-bin/chartstock.php?TickerSymbol=$symb&TimeRange=180&working_date=$pf_working_date&exch=$pf_exch&ChartSize=S&Volume=1&VGrid=1&HGrid=1&LogScale=0&ChartType=OHLC&Band=None&avgType1=SMA&movAvg1=10&avgType2=SMA&movAvg2=25&Indicator1=RSI&Indicator2=MACD&Indicator3=WilliamR&Indicator4=TRIX&Button1=Update%20Chart\" ALIGN=\"bottom\" BORDER=\"0\"></td>";
        }
        print "</tr>\n";
    }
    if (isset($_POST['chart']))
    {
        print "<tr><td colspan=\"10\"><input type=\"checkbox\" name=\"chart\" value=\"chart\" checked>Draw Charts</td>\n";
    }
    else
    {
        print "<tr><td colspan=\"10\"><input type=\"checkbox\" name=\"chart\" value=\"chart\">Draw Charts</td>\n";
    }
    print '<tr><td colspan="10"><input name="update" value="Update" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="sell" value="Sell" type="submit"/></td></tr>';
    print '</table>';
    print '</form>';
}

update_trades($pf_id);

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
elseif(isset($_POST['watch']))
{
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $symb)
        {
            if (! is_in_cart('watch', $symb))
            {
                if (add_to_cart('watch', $symb, $_POST["buy_comment_$symb"], $_POST["buy_volume_$symb"]))
                {
                    del_from_cart('cart', $symb);
                }
            }
        }
    }
}
elseif(isset($_POST['sell']))
{
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $symb)
        {
            sell_stock($symb, $_POST["buy_comment_$symb"], $_POST["buy_volume_$symb"]);
        }
    }
}


draw_table($pf_id, $pf_working_date, $pf_exch, $pf_name);
