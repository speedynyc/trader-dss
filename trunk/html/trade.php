<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('trade');
// Load the HTML_QuickForm module
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];

$portfolio = new portfolio($_SESSION['pfid']);
$pf_id = $portfolio->getID();
$pf_name = $portfolio->getName();
$pf_working_date = $portfolio->getWorkingDate();
$pf_exch = $portfolio->getExch()->getID();

try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}

function draw_buy_table($pf_id, $pf_working_date, $pf_exch, $pf_nam)
{
    global $pdo;
    print '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="cart" id="cart">';
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print '<tr><td>Symb</td><td>Name</td><td>Comment</td><td>Date</td><td>Volume</td><td>Close</td><td>Value</td>';
    if (isset($_SESSION['chart']))
    {
        print '<td>Chart</td></tr>';
        if (isset($_SESSION['chart_period']))
        {
            $chart_period = $_SESSION['chart_period'];
        }
        else
        {
            $chart_period = 180;
        }
    }
    print '</tr>';
    $query = "select * from cart where date <= '$pf_working_date' and pfid = '$pf_id' order by symb;";
    foreach ($pdo->query($query) as $row)
    {
        $symb = $row['symb'];
        $date = $row['date'];
        $symb_name = get_symb_name($symb, $pf_exch);
        $close = get_stock_close($symb, $pf_working_date, $pf_exch);
        $value = round($close*$row['volume'], 2);
        print "<tr><td><input type=\"checkbox\" name=\"mark[]\" value=\"$symb\">";
        print "<a href=\"/inspector.php?symb=$symb\" target=\"_blank\">$symb</a></td>\n";
        print "<td>$symb_name</td>\n";
        print "<td>\n";
        $warnings = get_warnings($symb, $pf_exch, $pf_working_date, $row['volume']);
        print "$warnings\n";
        print "<textarea wrap=\"soft\" rows=\"1\" cols=\"50\" name=\"comment_$symb\">" . $row['comment'] . '</textarea></td>';
        print "<td>$date<input type=\"hidden\" name=\"date_$symb\" value=\"$date\"></td>\n";
        print "<td><textarea wrap=\"soft\" rows=\"1\" cols=\"10\" name=\"volume_$symb\">" . $row['volume'] . '</textarea></td>';
        print "<td>$close</td>\n";
        print "<td>$value</td>\n";
        if (isset($_SESSION['chart']))
        {
            print "<td><img SRC=\"/cgi-bin/chartstock.php?TickerSymbol=$symb&TimeRange=$chart_period&working_date=$pf_working_date&exch=$pf_exch&ChartSize=S&Volume=1&VGrid=1&HGrid=1&LogScale=0&ChartType=OHLC&Band=None&avgType1=SMA&movAvg1=10&avgType2=SMA&movAvg2=25&Indicator1=RSI&Indicator2=MACD&Indicator3=WilliamR&Indicator4=TRIX&Button1=Update%20Chart\" ALIGN=\"bottom\" BORDER=\"0\"></td>";
        }
        print "</tr>\n";
    }
    if (isset($_SESSION['chart']))
    {
        print "<tr><td><table><tr><td><input type=\"checkbox\" name=\"chart\" value=\"chart\" checked>Draw Charts\n</td></tr><td> " . chart_select() . "</td></tr></table></td>\n";
    }
    else
    {
        print "<tr><td><table><tr><td><input type=\"checkbox\" name=\"chart\" value=\"chart\">Draw Charts\n</td></tr><td> " . chart_select() . "</td></tr></table></td>\n";
    }
    print '<td colspan="10"><input name="recalc" value="Update" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="delete" value="Delete" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="watch" value="Move to Watch list" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="buy" value="Buy" type="submit"/></td></tr>';
    print '</table>';
    print '</form>';
}

update_cart('cart', $portfolio);

function update_session()
{
    if (isset($_POST['chart']))
    {
        $_SESSION['chart'] = 1;
    }
    else
    {
        unset($_SESSION['chart']);
    }
    if (isset($_POST['chart_period']))
    {
        $_SESSION['chart_period'] = $_POST['chart_period'];
    }
    else
    {
        unset($_SESSION['chart_period']);
    }
}

if (isset($_POST['recalc']))
{
    update_session();
}
elseif (isset($_POST['delete']))
{
    update_session();
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $symb)
        {
            del_from_cart('cart', $symb);
        }
    }
}
elseif (isset($_POST['watch']))
{
    update_session();
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $symb)
        {
            if (! is_in_cart('watch', $symb))
            {
                if (add_to_cart('watch', $symb, $_POST["comment_$symb"], $_POST["volume_$symb"]))
                {
                    del_from_cart('cart', $symb);
                }
            }
        }
    }
}
elseif(isset($_POST['buy']))
{
    update_session();
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $symb)
        {
            if (buy_stock($symb, $_POST["comment_$symb"], $_POST["volume_$symb"]))
            {
                del_from_cart('cart', $symb);
            }
        }
    }
}

draw_buy_table($pf_id, $pf_working_date, $pf_exch, $pf_name);
?>
