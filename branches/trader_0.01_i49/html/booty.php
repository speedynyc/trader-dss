<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('booty');
// Load the HTML_QuickForm module
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$portfolio = new portfolio($_SESSION['pfid']);
$pf_id = $portfolio->getID();
$exch = $portfolio->getExch();
$pf_working_date = $portfolio->getWorkingDate();
$next_trade_day = $portfolio->getExch()->nextTradeDay($pf_working_date);
$cash_in_hand = $portfolio->getCashInHand();
$this_page = $_SERVER['REQUEST_URI'];

try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}

function draw_performance_table($portfolio)
{
    global $pdo;
    $pf_id = $portfolio->getID();
    $pf_name = $portfolio->getName();
    $pf_opening_balance = $portfolio->getOpeningBalance();
    $pf_opening_date = $portfolio->getStartDate();
    $pf_days_traded = $portfolio->countDaysTraded();
    $pf_cash_in_hand = $portfolio->getCashInHand();
    $pf_holdings = $portfolio->getHoldings();
    $pf_total = sprintf("%.2f", $pf_cash_in_hand + $pf_holdings);
    $pf_exchange_name = $portfolio->getExch()->getName();
    $pf_exch = $portfolio->getExch()->getID();
    $pf_working_date = $portfolio->getWorkingDate();
    $next_trade_day = $portfolio->getExch()->nextTradeDay($pf_working_date);
    print '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="cart" id="cart">';
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print '<tr><td>Symb</td><td>Name</td><td>Comment</td><td>Date of Purchase</td><td>Volume</td><td>Buy Price</td><td>close</td><td>gain</td><td>Value</td>';
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
    $query = "select * from holdings where pfid = '$pf_id' order by symb;";
    foreach ($pdo->query($query) as $row)
    {
        $symb = $row['symb'];
        $hid = $row['hid'];
        $symb_name = get_symb_name($symb, $pf_exch);
        $price = $row['price'];
        $close = get_stock_close($symb, $pf_working_date, $pf_exch);
        $price_diff_pc = round(((($close-$price)/$price)), 2)*100;
        $volume = $row['volume'];
        $value = sprintf("%.2f", round($close*$volume, 2));
        $date = $row['date'];
        if ($price_diff_pc == 0)
        {
            $colour = 'black';
        }
        elseif ($price_diff_pc > 0)
        {
            if ( $volume > 0)
            {
                $colour = 'green';
            }
            else
            {
                $colour = 'red';
                $price_diff_pc = 0 - $price_diff_pc;
            }
        }
        else
        {
            if ( $volume > 0)
            {
                $colour = 'red';
            }
            else
            {
                $colour = 'green';
                $price_diff_pc = 0 - $price_diff_pc;
            }
        }
        print "<tr><td><input type=\"checkbox\" name=\"mark[]\" value=\"$hid\"><font color=\"$colour\">$symb</font></td>\n";
        print "<td><font color=\"$colour\">$symb_name</font></td>\n";
        print "<td><textarea wrap=\"soft\" rows=\"1\" cols=\"50\" name=\"comment_$hid\">" . $row['comment'] . '</textarea></td>';
        print "<td>$date<input type=\"hidden\" name=\"date_$symb\" value=\"$date\"></td>\n";
        print "<td align=\"right\">" . $row['volume'] . '</td>';
        print "<td align=\"right\">$price</td>\n";
        print "<td align=\"right\">$close</td>\n";
        print "<td align=\"right\">$price_diff_pc %</td>\n";
        print "<td align=\"right\">$value</td>\n";
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
    print '<td colspan="10"><input name="update" value="Update" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="sell" value="Sell" type="submit"/></td></tr>';
    print '<tr><td colspan="10"><input name="next_day" value="Go to the next day, '.$next_trade_day.'" type="submit"/></td></tr>';
    print '</table>';
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print "<tr><td align=\"left\">Portfolio:</td><td>$pf_name</td></tr>\n";
    print "<tr><td align=\"left\">Exchange:</td><td>$pf_exchange_name</td></tr>\n";
    print "<tr><td align=\"left\">Opening date:</td><td align=\"right\">$pf_opening_date</td>\n";
    print "<tr><td align=\"left\">Days Traded:</td><td align=\"right\">$pf_days_traded</td>\n";
    print "<tr><td align=\"left\">Opening Balance:</td><td align=\"right\">$pf_opening_balance</td>\n";
    print "<tr><td align=\"left\">Cash In Hand:</td><td align=\"right\">$pf_cash_in_hand</td>\n";
    print "<tr><td align=\"left\">Holdings:</td><td align=\"right\">$pf_holdings</td>\n";
    print "<tr><td align=\"left\">Total:</td><td align=\"right\">$pf_total</td>\n";
    print "<tr><td colspan=\"10\"><img src=\"/cgi-bin/portfolio_chart.php?pfid=$pf_id\"/></td></tr>";
    print '</form>';
}

function update_session()
{
    // save any changes that have been typed into the form
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

if (isset($_POST['update']))
{
    update_session();
    update_holdings($portfolio);
}
elseif (isset($_POST['next_day']))
{
    update_session();
    try 
    {
        $pdo->beginTransaction();
        // step portfolios on to the next day
        $query = "update portfolios set working_date = '$next_trade_day' where pfid = '$pf_id';";
        $pdo->exec($query);
        // copy the row forward for pf_summary
        // work out what the value of heach holding is with the new close price
        $query = "select sum((holdings.price * abs(holdings.volume)) + (holdings.volume * (quotes.close - holdings.price))) as value from holdings, quotes where holdings.symb = quotes.symb and quotes.date = '$next_trade_day' and holdings.pfid = '$pf_id';";
        foreach ($pdo->query($query) as $row)
        {
            $holdings = $row['value'];
        }
        if ($holdings == '')
        {
            $holdings = 0;
        }
        $query = "insert into pf_summary (pfid, date, cash_in_hand, holdings) values ('$pf_id', '$next_trade_day', '$cash_in_hand', '$holdings');";
        $pdo->exec($query);
        $pdo->commit();
    }
    catch (PDOException $e)
    {
        tr_warn('booty.php: next day failed' . $query . ':' . $e->getMessage());
    }
    // must save the session and redraw the page because the header is already drawn and can't be updated with the new totals
    update_holdings($portfolio);
    header("Location: $this_page");
    exit;
}
elseif(isset($_POST['sell']))
{
    update_session();
    if (isset($_POST['mark']))
    {
        $marked = $_POST['mark'];
        foreach ($marked as $hid)
        {
            $symb = get_hid_symb($hid);
            sell_stock($hid, $symb, $_POST["comment_$hid"]);
        }
        $portfolio = new portfolio($_SESSION['pfid']);
    }
    update_holdings($portfolio);
    // must save the session and redraw the page because the header is already drawn and can't be updated with the new totals
    header("Location: $this_page");
    exit;
}

draw_performance_table($portfolio);
