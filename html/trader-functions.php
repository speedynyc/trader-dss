<?php

# set some globals
$db_hostname = 'localhost';
$db_database = 'trader';
$db_user     = 'postgres';
$db_password = 'happy';

function tr_warn($message='No message!')
{
    print('<font color="red">' . $message . '</font><br>');
}

function update_holdings($portfolio)
{
    // update the comment in the holdings table
    global $db_hostname, $db_database, $db_user, $db_password;
    $pfid = $portfolio->getID();
    $pf_working_date = $portfolio->getWorkingDate();
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select * from holdings where pfid = '$pfid' order by symb;";
    foreach ($pdo->query($query) as $row)
    {
        $hid = $row['hid'];
        if (isset($_POST["comment_$hid"]))
        {
            $comment = $_POST["comment_$hid"];
            $update = "update holdings set comment = '$comment' where hid = '$hid';";
            try 
            {
                $pdo->exec($update);
            }
            catch (PDOException $e)
            {
                tr_warn('update_holdings:' . $update . ':' . $e->getMessage());
            }
        }
    }
}

function update_cart($cart, $portfolio)
{
    // this function is probably only going to be used by trade and watch so really shouldn't be here
    global $db_hostname, $db_database, $db_user, $db_password;
    $pfid = $portfolio->getID();
    $pf_working_date = $portfolio->getWorkingDate();
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select * from $cart where date <= '$pf_working_date' and pfid = '$pfid';";
    foreach ($pdo->query($query) as $row)
    {
        $symb = $row['symb'];
        if (isset($_POST["volume_$symb"]))
        {
            $volume = $_POST["volume_$symb"];
            $date = $_POST["date_$symb"];
            if (is_numeric($volume))
            {
                $update = "update $cart set volume = '$volume' where pfid = '$pfid' and date = '$date' and symb = '$symb';";
                try 
                {
                    $pdo->exec($update);
                }
                catch (PDOException $e)
                {
                    tr_warn('update_cart:' . $update . ':' . $e->getMessage());
                }
            }
        }
        if (isset($_POST["comment_$symb"]))
        {
            $comment = $_POST["comment_$symb"];
            $date = $_POST["date_$symb"];
            $update = "update $cart set comment = '$comment' where pfid = '$pfid' and date = '$date' and symb = '$symb';";
            try 
            {
                $pdo->exec($update);
            }
            catch (PDOException $e)
            {
                tr_warn('update_cart:' . $update . ':' . $e->getMessage());
            }
        }
    }
}

function sell_stock($hid, $symb, $comment = '')
{
    // Move stock out of holdings, add a record to trades and update pf_summary
    global $db_hostname, $db_database, $db_user, $db_password;
    $portfolio = new portfolio($_SESSION['pfid']);
    $pfid = $portfolio->getID();
    $date = $portfolio->getWorkingDate();
    $exch = $portfolio->getExch()->getID();
    $close = get_stock_close($symb, $date, $exch);
    $qty = get_hid_volume($hid);
    $buy_price = get_hid_buy_price($hid);
    $commission = $portfolio->getCommission();
    // this will work correctly for both longs and shorts. See the wiki
    $total = ($buy_price * abs($qty)) + ($qty * ($close - $buy_price));
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: sell_stock Cannot connect: " . $e->getMessage());
    }
    // find out how much money, and stocks are in the portfolio today
    $query = "select cash_in_hand, holdings from pf_summary where pfid = '$pfid' and date = '$date';";
    foreach ($pdo->query($query) as $row)
    {
        $cash_in_hand = $row['cash_in_hand'];
        $holdings = $row['holdings'];
    }
    $cash_in_hand = $cash_in_hand + $total - $commission;
    $holdings = $holdings - $total;
    try
    {
        $query = "select nextval('trades_trid_seq') as trid;";
        $result = $pdo->query($query);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_trid = $row['trid'];
    }
    catch (PDOException $e)
    {
        tr_warn('sell_stock:' . $query . ':' . $e->getMessage());
        return false;
    }
    try 
    {
        $pdo->beginTransaction();
        // add the trade to the trades table
        $qty = 0 - $qty; # we're selling after all.
        $query = "insert into trades (trid, pfid, hid, date, symb, price, volume, comment, tr_type) values ($next_trid, $pfid, $hid, '$date', '$symb', $close, $qty, '$comment', 'C');";
        $pdo->exec($query);
        // delete the stock from the holdings table
        $query = "delete from holdings where hid = '$hid';";
        $pdo->exec($query);
        // update the pf_summary with the trade
        $query = "update pf_summary set cash_in_hand = '$cash_in_hand', holdings = '$holdings' where date = '$date' and pfid = '$pfid';";
        $pdo->exec($query);
        $pdo->commit();
    }
    catch (PDOException $e)
    {
        $pdo->rollBack();
        tr_warn('sell_stock:' . $query . ':' . $e->getMessage());
        return false;
    }
    return true;
}

function buy_stock($symb, $comment = '', $volume = 0)
{
    // moves stock from the cart to trades and updates pf_summary
    global $db_hostname, $db_database, $db_user, $db_password;
    $portfolio = new portfolio($_SESSION['pfid']);
    $pfid = $portfolio->getID();
    $date = $portfolio->getWorkingDate();
    $exch = $portfolio->getExch()->getID();
    $commission = $portfolio->getCommission();
    $close = get_stock_close($symb, $date, $exch);
    if ($comment == '')
    {
        $comment = "$name: $date";
    }
    if ($volume != 0)
    {
        $qty = $volume;
    }
    else
    {
        // this means that a volume of '0' buys one parcel's worth.
        // is that what we want?
        if ($close < $parcel)
        {
            $qty = (int)($parcel/$close);
        }
        else
        {
            $qty = 1;
        }
    }
    // take the absolute value of $qty since we set aside the purchase price as an asset for a short.
    $total = ($close * abs($qty));
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    // find out how much money, and stocks are in the portfolio today
    $query = "select cash_in_hand, holdings from pf_summary where pfid = '$pfid' and date = '$date';";
    foreach ($pdo->query($query) as $row)
    {
        $cash_in_hand = $row['cash_in_hand'];
        $holdings = $row['holdings'];
    }
    $duty = $total * ($portfolio->getTaxRate()/100);
    if ($cash_in_hand < ($total + $duty + $commission))
    {
        // we can't afford this one!
        return false;
    }
    $cash_in_hand = $cash_in_hand - $total - $duty - $commission;
    $holdings = $holdings + $total;
    try
    {
        $query = "select nextval('trades_trid_seq') as trid;";
        $result = $pdo->query($query);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_trid = $row['trid'];
    }
    catch (PDOException $e)
    {
        tr_warn('buy_stock:' . $query . ':' . $e->getMessage());
        return false;
    }
    try
    {
        $query = "select nextval('holdings_hid_seq') as hid;";
        $result = $pdo->query($query);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_hid = $row['hid'];
    }
    catch (PDOException $e)
    {
        tr_warn('buy_stock:' . $query . ':' . $e->getMessage());
        return false;
    }
    try 
    {
        $pdo->beginTransaction();
        // add the trade to the trades table
        $query = "insert into trades (trid, pfid, hid, date, symb, price, volume, comment, tr_type) values ('$next_trid', '$pfid', '$next_hid', '$date', '$symb', '$close', '$qty', '$comment', 'O');";
        $pdo->exec($query);
        // add the transaction to the holdings table
        $query = "insert into holdings (hid, pfid, date, symb, price, volume, comment) values ('$next_hid', '$pfid', '$date', '$symb', '$close', '$qty', '$comment');";
        $pdo->exec($query);
        // update the pf_summary with the trade
        $query = "update pf_summary set cash_in_hand = '$cash_in_hand', holdings = '$holdings' where date = '$date' and pfid = '$pfid';";
        $pdo->exec($query);
        $pdo->commit();
    }
    catch (PDOException $e)
    {
        $pdo->rollBack();
        tr_warn('buy_stock:' . $query . ':' . $e->getMessage());
        return false;
    }
    return true;
}

function add_to_cart($table, $symb, $comment = '', $volume = 0)
{
    // adds all symbols in the given list to the given table
    global $db_hostname, $db_database, $db_user, $db_password;
    $portfolio = new portfolio($_SESSION['pfid']);
    $pfid = $portfolio->getID();
    $date = $portfolio->getWorkingDate();
    $exch = $portfolio->getExch()->getID();
    $parcel = $portfolio->getParcel();
    if (isset($_SESSION['sql_name']))
    {
        $name = $_SESSION['sql_name'];
    }
    else
    {
        $name = $portfolio->getName();
    }
    $close = get_stock_close($symb, $date, $exch);
    if ($comment == '')
    {
        $comment = "$name: $date";
    }
    if ($volume != 0)
    {
        $qty = $volume;
    }
    else
    {
        if ($close < $parcel)
        {
            $qty = (int)($parcel/$close);
        }
        else
        {
            $qty = 1;
        }
    }
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "insert into $table (pfid, date, symb, volume, comment) values ('$pfid', '$date', '$symb', '$qty', '$comment');";
    try 
    {
        $pdo->exec($query);
        return true;
    }
    catch (PDOException $e)
    {
        tr_warn('add_to_cart:' . $query . ':' . $e->getMessage());
        return false;
    }
}

function is_in_cart($table, $symb)
{
    // checks if the given symbol is already in the given cart
    global $db_hostname, $db_database, $db_user, $db_password;
    $pfid = $_SESSION['pfid'];
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select count(*) from $table where pfid = '$pfid' and symb = '$symb';";
    try 
    {
        $result = $pdo->query($query);
    }
    catch (PDOException $e)
    {
        tr_warn('is_in_cart:' . $query . ':' . $e->getMessage());
        return false;
    }
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return ($row['count'] > 0);
}

function del_from_cart($table, $symb)
{
    // removes a symbol from a cart
    $pfid = $_SESSION['pfid'];
    $date = $_POST["date_$symb"];
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "delete from $table where symb = '$symb' and date = '$date' and pfid = '$pfid';";
    try 
    {
        $pdo->exec($query);
        return true;
    }
    catch (PDOException $e)
    {
        tr_warn('del_from_cart:' . $query . ':' . $e->getMessage());
        return false;
    }
}

function t_for_true($value)
{
    // returns true if $value is 't' and false for every other value
    if (isset($value))
    {
        if ($value == 't')
        {
            return true;
        }
    }
    return false;
}

function redirect_login_pf()
{
    /* the idea here is to redirect to the login or portfolio selection page when 
       the cookie doesn't contain a valid username or portfolio.
       This will stop someone from opening a browser and getting some page other than the login page
     */
    global $scramble_names;
    session_start();
    $login_page = "/login.php";
    $portfolio_page = "/portfolios.php";
    $URI = $_SERVER['REQUEST_URI'];
    // if a username isn't set, redirect to the login page
    if (! isset($_SESSION['username']))
    {
        // don't redirect if we're already heading for it
        if ( $URI != $login_page )
        {
            header("Location: $login_page");
            exit;
        }
    }
    // otherwise if a portfolio hasn't been selected, select one
    elseif (! isset($_SESSION['pfid']))
    {
        // don't redirect if we're already heading for it
        if ( $URI != $portfolio_page )
        {
            header("Location: $portfolio_page");
            exit;
        }
    }
    if (isset($_SESSION['pfid']))
    {
        $portfolio = new portfolio($_SESSION['pfid']);
        $scramble_names = $portfolio->symbNamesHidden();
    }
}

function draw_cell($cell_desc, $cell_link, $cell_colour, $cell_selectable)
{
    print "<td bgcolor=\"$cell_colour\">";
    if ($cell_selectable)
    {
        print "<a href=\"$cell_link\">$cell_desc</a>";
    }
    else
    {
        print "$cell_desc";
    }
    print "</td>\n";
}

function draw_summary($username, $pf_name, $exch_name, $working_date, $query_name, $chart_name)
{
    print '<table border="0" cellpadding="5" cellspacing="0" width="90%" align="center">';
    if ($username == 'N/A')
    {
        print '<tr><td></td>';
    }
    else
    {
        print "<tr><td>User: $username</td>";
    }
    if ($pf_name == 'N/A')
    {
        print '<td></td>';
    }
    else
    {
        print "<td>Portfolio: $pf_name</td>";
    }
    if ($exch_name == 'N/A')
    {
        print '<td></td>';
    }
    else
    {
        print "<td>Exchange: $exch_name</td>";
    }
    if ($working_date == 'N/A')
    {
        print '<td></td>';
    }
    else
    {
        print "<td>Working Date: $working_date</td>";
    }
    if ($query_name == 'N/A')
    {
        print '<td></td>';
    }
    else
    {
        print "<td>Query: $query_name</td>";
    }
    if ($chart_name == 'N/A')
    {
        print '<td></td>';
        print "<td></td></tr></table>\n";
    }
    else
    {
        print "<td>Chart: $chart_name</td></tr></table>\n";
    }
}

function draw_trader_header($active_page, $allow_others=true)
{
    // dray the header with a table linking the trader pages like tabs in a notebook
    // session infomation is used to communicate between the tabs
    $active_page = strtolower($active_page);
    $active_colour = 'white';
    $inactive_colour = 'grey';
    if (! isset($_SESSION['uid']))
    {
        // don't allow other page links if uid isn't set
        $active_page = 'login';
        $allow_others = false;
    }
    elseif (! isset($_SESSION['pfid']))
    {
        // uid is set but pf id isn't. Only allowable active page is 'login' or 'portfolio'
        if ($active_page != 'login' and $active_page != 'portfolios')
        {
            // force to the portfolio page
            $active_page = 'portfolios';
        }
        $allow_others = false;
    }
    if (isset($_SESSION['username']))
    {
        $username = $_SESSION['username'];
    }
    else
    {
        $username = 'N/A';
    }
    if (isset($_SESSION['pfid']))
    {
        $portfolio = new portfolio($_SESSION['pfid']);
        $pfid = $portfolio->getID();
        $pf_name = $portfolio->getName();
        $exch = $portfolio->getExch();
        $exch_name = $exch->getName();
        $working_date = $portfolio->getWorkingDate();
        $pf_gain = $portfolio->dayGain(1);
        $pf_CIH = $portfolio->getCashInHand();
        $pf_currency_symb = $exch->getCurrency();
        if ($pf_gain > 0)
        {
            $pf_gain = sprintf("%.2f", $pf_gain);
            $pf_gain = "<font color=\"green\">+$pf_currency_symb$pf_gain</font>";
        }
        else
        {
            $pf_gain = sprintf("%.2f", $pf_gain);
            $pf_gain = "<font color=\"red\">$pf_currency_symb$pf_gain</font>";
        }
        $pf_name = "$pf_name ($pf_currency_symb$pf_CIH/$pf_gain)";
    }
    else
    {
        $pf_name = $exch_name = $working_date = 'N/A';
    }
    if (isset($_SESSION['sql_name']))
    {
        $qid = $_SESSION['qid'];
        $query_name = $_SESSION['sql_name'];
        $query_name = "$query_name ($qid)";
    }
    else
    {
        $query_name = 'N/A';
    }
    if (isset($_SESSION['chart_name']))
    {
        $chid = $_SESSION['chid'];
        $chart_name = $_SESSION['chart_name'];
        $chart_name = "$chart_name ($chid)";
    }
    else
    {
        $chart_name = 'N/A';
    }
    switch ($active_page) {
        case 'login':
            print "<html><title>Trader Login</title><body>\n";
            break;
        case 'portfolios':
            print "<html><title>Select or Create a Portfolio</title><body>\n";
            break;
        case 'booty':
            print "<html><title>Report Portfolio Performance</title><body>\n";
            break;
        case 'select':
            print "<html><title>Choose Securities to trade</title><body>\n";
            break;
        case 'trade':
            print "<html><title>Buy selected Securities</title><body>\n";
            break;
        case 'watch':
            print "<html><title>Watch with a view to buy</title><body>\n";
            break;
        case 'queries':
            print "<html><title>Create queries to find securities</title><body>\n";
            break;
        case 'chart':
            print "<html><title>Inspect all symbols</title><body>\n";
            break;
        case 'history':
            print "<html><title>Historical trades</title><body>\n";
            break;
        case 'docs':
            print "<html><title>Documentation of the Trader Relations</title><body>\n";
            break;
        default:
            tr_warn("[FATAL]Cannot create header, given $active_page\n");
            $active_page = 'login';
            $allow_others = false;
            break;
    }
    print '<table width="100%" border="0">';
    print '<table border="1" cellpadding="5" cellspacing="0" width="100%" align="center">';
    print "\n<tr><td colspan=\"100\" valign=\"bottom\" bgcolor=\"$inactive_colour\"><h1 style=\"font-family:verdana\">Trader DSS</h1></td></tr><tr>\n";
    if ($active_page == 'login')
    {
        draw_cell($active_page, '/login.php', $active_colour, true);
    }
    else
    {
        // must always be possible to choose the login page
        draw_cell('login', '/login.php', $inactive_colour, true);
    }
    if ($active_page == 'portfolios')
    {
        draw_cell($active_page, '/portfolios.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('portfolios', '/portfolios.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'booty')
    {
        draw_cell($active_page, '/booty.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('booty', '/booty.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'select')
    {
        draw_cell($active_page, '/select.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('select', '/select.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'trade')
    {
        draw_cell($active_page, '/trade.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('trade', '/trade.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'watch')
    {
        draw_cell($active_page, '/watch.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('watch', '/watch.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'queries')
    {
        draw_cell($active_page, '/queries.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('queries', '/queries.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'chart')
    {
        draw_cell($active_page, '/chart.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('chart', '/chart.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'history')
    {
        draw_cell($active_page, '/history.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('history', '/history.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'docs')
    {
        draw_cell($active_page, '/docs.php', $active_colour, $allow_others);
    }
    else
    {
        draw_cell('docs', '/docs.php', $inactive_colour, $allow_others);
    }
    draw_summary($username, $pf_name, $exch_name, $working_date, $query_name, $chart_name);
    print "</tr></table></table>\n";
}

function get_symb_max_price($symb, $exch, $buy_date, $date)
{
    // returns the max close between the dates
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select max(close) as max from quotes where symb = '$symb' and exch = '$exch' and date >= '$buy_date' and date <= '$date';";
    foreach ($pdo->query($query) as $row)
    {
        return $row['max'];
    }
    return -1;
}

function get_symb_min_price($symb, $exch, $buy_date, $date)
{
    // returns the min close between the dates
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select min(close) as min from quotes where symb = '$symb' and exch = '$exch' and date >= '$buy_date' and date <= '$date';";
    foreach ($pdo->query($query) as $row)
    {
        return $row['min'];
    }
    return -1;
}

function get_hid_symb($hid)
{
    // return the symbol name of a holding
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select symb from holdings where hid = '$hid';";
    foreach ($pdo->query($query) as $row)
    {
        return $row['symb'];
    }
    return 'Unknown hid';
}

function get_hid_buy_price($hid)
{
    // return the symbol name of a holding
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select price from holdings where hid = '$hid';";
    foreach ($pdo->query($query) as $row)
    {
        return $row['price'];
    }
    return 'Unknown hid';
}

function get_hid_volume($hid)
{
    // return the symbol name of a holding
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select volume from holdings where hid = '$hid';";
    foreach ($pdo->query($query) as $row)
    {
        return $row['volume'];
    }
    return 'Unknown hid';
}

function is_in_portfolio($symb, $pfid)
{
    // lookup holdings to see if the symbol's there
    return is_in_cart('holdings', $symb);
}

function gain($symb, $exch, $pfid, $pf_working_date)
{
    // work if the symbol has gaind value since it was bought
    // remember to treat shorts in the opposite way to longs
    // there might be several in holdings so average them all
    global $db_hostname, $db_database, $db_user, $db_password;
    $t_volume = 0;
    $t_price = 0;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $close = get_stock_close($symb, $pf_working_date, $exch);
    $query = "select price, volume from holdings where symb = '$symb';";
    foreach ($pdo->query($query) as $row)
    {
        $t_volume = $t_volume + $row['volume'];
        $t_price = $t_price + ($row['volume'] * $row['price']);
    }
    $avg_price = $t_price / $t_volume;
    if ( $t_volume < 0 )
    {
        // we're shorting
        if ($close > $avg_price)
        {
            return -1;
        }
        elseif ($close < $avg_price)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
    else
    {
        // we're going long
        if ($close > $avg_price)
        {
            return 1;
        }
        elseif ($close < $avg_price)
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }
}

function get_symb_name_coloured($symb, $exch, $pfid, $pf_working_date)
{
    // retrieve any field from a table indexed on symb, date, exch
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    if (isset($_SESSION['pfid']))
    {
        // work out if the symbol is already in the portfolio and if it's winning or losing
        if (is_in_portfolio($symb, $pfid))
        {
            $gain = gain($symb, $exch, $pfid, $pf_working_date);
            if ( $gain == 0 )
            {
                $colour = 'orange';
            }
            elseif ($gain > 0 )
            {
                $colour = 'green';
            }
            else
            {
                $colour = 'red';
            }
        }
        else
        {
            $colour = 'black';
        }
    }
    $name = get_symb_name($symb, $exch);
    if ($name)
    {
        return "<font color=\"$colour\">$name</font>";
    }
    else
    {
        return false;
    }
}

function get_symb_name($symb, $exch)
{   
    // retrieve any field from a table indexed on symb, date, exch
    global $db_hostname, $db_database, $db_user, $db_password, $scramble_names;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select name from stocks where symb = '$symb' and exch = '$exch';";
    foreach ($pdo->query($query) as $row)
    {
        if ($scramble_names)
        {
            return substr(md5($row['name']), 0, 10);
        }
        else
        {
            return $row['name'];
        }
    }
    return false;
}   

function get_stock_close($symb, $date, $exch)
{
    return get_table_field('quotes', 'close', $symb, $date, $exch);
}

function get_table_field($table, $field, $symb, $date, $exch)
{
    // retrieve any field from a table indexed on symb, date, exch
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select $field from $table where date = '$date' and symb = '$symb' and exch = '$exch';";
    try 
    {
        $result = $pdo->query($query);
    }
    catch (PDOException $e)
    {
        tr_warn('get_table_field' . $query . ':' . $e->getMessage());
        die("[FATAL]\n");
    }
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if (isset($row[$field]))
    {
        return $row[$field];
    }
    return false;
}

function chart_option($value, $string, $selected = 0)
{
    // return an option output selected if $value == $selected
    if ($value == $selected)
    {
        return "<option value=\"$value\" selected=\"selected\">$string</option>";
    }
    else
    {
        return "<option value=\"$value\">$string</option>";
    }
}

function chart_select()
{
    // print a select list to choose the period to chart
    $select_string = '';
    if (isset($_SESSION['chart_period']))
    {
        $chart_period = $_SESSION['chart_period'];
    }
    else
    {
        $chart_period = 0;
    }
    $select_string = "<select name=\"chart_period\">\n";
    $select_string = $select_string . chart_option(7, '1 week', $chart_period) . "\n";
    $select_string = $select_string . chart_option(30, '1 month', $chart_period) . "\n";
    $select_string = $select_string . chart_option(60, '2 months', $chart_period) . "\n";
    $select_string = $select_string . chart_option(90, '3 months', $chart_period) . "\n";
    $select_string = $select_string . chart_option(180, '6 months', $chart_period) . "\n";
    $select_string = $select_string . chart_option(365, '1 year', $chart_period) . "\n";
    $select_string = $select_string . chart_option(730, '2 years', $chart_period) . "\n";
    $select_string = $select_string . chart_option(1905, '5 years', $chart_period) . "\n";
    $select_string = $select_string . chart_option(3650, '10 years', $chart_period) . "\n";
    $select_string = "$select_string </select>\n";
    return $select_string;
}

function get_warnings($symb, $pf_exch, $pf_working_date, $volume)
{
    // do a bunch of checks and return warning strings
    $warnings = '';
    // check that the volume and the moving average match
    $ma_10_diff = get_table_field('moving_averages', 'ma_10_diff', $symb, $pf_working_date, $pf_exch);
    if ($volume > 0 and $ma_10_diff < 0)
    {
        $warnings .= '<font color="red">Going long on a falling MA</font><br>';
    }
    elseif ($volume < 0 and $ma_10_diff > 0)
    {
        $warnings .= '<font color="red">Going short on a rising MA</font><br>';
    }
    return $warnings;
}

abstract class trader_base
{
    // a base class to stop auto-vivication of object variables
    // and to setup the DB connection
    protected $dbh;
    public function __construct()
    {
        // setup the DB connection for use in this script
        global $db_hostname, $db_database, $db_user, $db_password;
        try {
            $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("ERROR: Cannot connect: " . $e->getMessage());
        }
        $this->dbh = $pdo;
    }
    protected function __set($name, $value)
    {
        tr_warn("No such property trader_base __set(): $name = $value");
        die("Object error");
    }
    protected function __get($name)
    {
        tr_warn("No such property trader_base __get(): $name");
        die("Object error");
    }
    protected function get($name)
    {
        if (isset($this->$name))
        {
            return $this->$name;
        }
        else
        {
            die("[FATAL]: No such property  trader_base get() portfolio->$name\n");
        }
    }
}

class exchange extends trader_base
{
    protected $exch, $name, $symb, $currency;
    public function __construct($exch_id)
    {
        parent::__construct();
        $query = "select * from exchange where exch = '$exch_id';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('exchange:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: exchange, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['exch']) and $row['exch'] == $exch_id)
        {
            $this->exch = $row['exch'];
            $this->name = $row['name'];
            $this->symb = $row['curr_desc'];
            $this->currency = $row['curr_char'];
        }
        else
        {
            die("[FATAL]exchange $exch_id missing from exchange table: $query\n");
        }
    }
    public function getID() { return $this->exch; }
    public function getName() { return $this->name; }
    public function getSymb() { return $this->symb; }
    public function getCurrency() { return $this->currency; }
    public function nextTradeDay($date)
    {
        // returns the next trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where date > '$date' and exch = '$exch' order by date asc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('nextTradeDay:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
    public function nearestTradeDay($date)
    {
        // returns the nearest trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where date >= '$date' and exch = '$exch' order by date asc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('nearestTradeDay:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
    public function firstDate()
    {
        // returns the first trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where exch = '$exch' order by date asc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('firstDate:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
    public function lastDate()
    {
        // returns the first trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where exch = '$exch' order by date desc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('lastDate:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
}

class portfolio extends trader_base
{
    protected $pfid, $name, $exch, $parcel, $working_date, $hide_names, $stop_loss, $auto_stop_loss;
    protected $cashInHand, $holdings, $openingBalance, $startDate, $countOfDaysTraded;
    protected $commission, $tax_rate;
    public function __construct($pfid)
    {
        // setup the the parent class (db connection etc)
        parent::__construct();
        // set all the 'lazy evaluate values to impossible numbers
        $this->countOfDaysTraded = -1;
        $query = "select * from portfolios where pfid = '$pfid';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('portfolio:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['pfid']) and $row['pfid'] == $pfid)
        {
            $this->pfid = $row['pfid'];
            $this->name = $row['name'];
            $this->exch = new exchange($row['exch']);
            $this->openingBalance = $row['opening_balance'];
            $this->parcel = $row['parcel'];
            $this->working_date = $row['working_date'];
            $this->hide_names = t_for_true($row['hide_names']);
            $this->stop_loss = $row['stop_loss'];
            $this->auto_stop_loss = t_for_true($row['auto_stop_loss']);
            $this->commission = $row['commission'];
            $this->tax_rate = $row['tax_rate'];
        }
        else
        {
            die("[FATAL]portfolio $pfid missing from portfolios table: $query\n");
        }
        // get the start date by finding the first record for the portfolio in pf_summary
        $query = "select * from pf_summary where pfid = '$pfid' order by date asc limit 1";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('portfolio:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['pfid']) and $row['pfid'] == $pfid)
        {
            $this->startDate = $row['date'];
        }
        // get current balance and holdings by finding the most recent entry in pf_summary
        $query = "select * from pf_summary where pfid = '$pfid' order by date desc limit 1";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('portfolio:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['pfid']) and $row['pfid'] == $pfid)
        {
            $this->cashInHand = $row['cash_in_hand'];
            $this->holdings = $row['holdings'];
        }
        else
        {
            tr_warn('portfolio:__construct: $row[\'pfid\'] not defined: ' . $query);
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
    }
    public function getID() { return $this->pfid; }
    public function getExch() { return $this->exch; }
    public function getName() { return $this->name; }
    public function getStopLoss() { return $this->stop_loss; }
    public function getAutoStopLoss() { return $this->auto_stop_loss; }
    public function symbNamesHidden() { return $this->hide_names; }
    public function getWorkingDate() { return $this->working_date; }
    public function getParcel() { return $this->parcel; }
    public function getStartDate() { return $this->startDate; }
    public function getCashInHand() { return $this->cashInHand; }
    public function getHoldings() { return $this->holdings; }
    public function getCommission() { return $this->commission; }
    public function getTaxRate() { return $this->tax_rate; }
    public function getOpeningBalance() { return $this->openingBalance; }
    public function countDaysTraded()
    {
        // returns the next trading day for the exchange
        $pfid = $this->pfid;
        if ($this->countOfDaysTraded == -1)
        {
            $query = "select count(*) as days from pf_summary where pfid = '$pfid';";
            try 
            {
                $result = $this->dbh->query($query);
            }
            catch (PDOException $e)
            {
                tr_warn('countDaysTraded:' . $query . ':' . $e->getMessage());
            }
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $this->countOfDaysTraded = $row['days'];
            return $this->countOfDaysTraded;
        }
        else
        {
            return $this->countOfDaysTraded;
        }
    }
    public function dayGain($days=1)
    {
        $current_total = $this->cashInHand + $this->holdings;
        $pfid = $this->pfid;
        $working_date = $this->working_date;
        /*
           set compare_total to working_total so that if we only have the first record (a new portfolio for exahple)
           It doesn't appear as a gain of the opening balance.
         */
        $previous_total = $current_total;
        // simple hack, we select $days days before today and the last one we reach is the one we want
        $query = "select pfid, date, (cash_in_hand + holdings) as total from pf_summary where pfid = '$pfid' and date < '$working_date' order by date desc limit '$days'";
        foreach ($this->dbh->query($query) as $row)
        {
            $previous_total = $row['total'];
        }
        return $current_total - $previous_total;
    }
}

class security extends trader_base
{
    protected $symb, $name, $exch, $pfid;
    protected $firstQuote, $lastQuote;
    public function __construct($symb, $exch)
    {
        // setup the the parent class (db connection etc)
        parent::__construct();
        $this->exch = new exchange($exch);
        if (isset($_SESSION['pfid']))
        {
            $this->pfid = $_SESSION['pfid'];
        }
        else
        {
            $this->pfid = -1;
        }
        // load the info from the stocks table
        $query = "select * from stocks where symb = '$symb' and exch = '$exch';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('security:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: security, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['symb']) and $row['symb'] == $symb)
        {
            $this->name = $row['name'];
            $this->firstQuote = $row['first_quote'];
            $this->lastQuote = $row['last_quote'];
        }
    }
    protected function isInTable($table)
    {
        // check if the symbol is held in the current portfolio
        if ($this->pfid > 0)
        {
            $symb = $this->symb;
            $pfid = $this->pfid;
            $query = "select count(*) as count from $table where symb = '$symb' and pfid = '$pfid';";
            try 
            {
                $result = $this->dbh->query($query);
            }
            catch (PDOException $e)
            {
                tr_warn('security:isInTable:' . $query . ':' . $e->getMessage());
                die("[FATAL]Class: security, function: isInTable\n");
            }
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if (isset($row['count']) and $row['count'] > 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    public function isInHolding() { return isInTable('holdings'); }
    public function isInCart() { return isInTable('cart'); }
    public function isInWatch() { return isInTable('watch'); }
}

class quote extends security
{
    protected $open, $close, $high, $low, $volume;
    protected $dates, $highData, $lowData, $openData, $closeData, $volData;
    protected $max, $min, $maxDate, $minDate;
    protected $loadedStartDate, $loadedEndDate;
    public function __construct($symb, $exch, $date)
    {
        // setup the the parent class (db connection etc)
        parent::__construct($symb, $exch);
        // load the info from the stocks table
        $query = "select * from quotes where symb = '$symb' and exch = '$exch' and date = '$date';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('quote:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: quote, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['symb']) and $row['symb'] == $symb)
        {
            $this->open = $row['open'];
            $this->high = $row['high'];
            $this->low = $row['low'];
            $this->close = $row['close'];
            $this->volume = $row['volume'];
        }
    }
    public function getOpen() { return $this->open; }
    public function getHigh() { return $this->high; }
    public function getLow() { return $this->low; }
    public function getClose() { return $this->close; }
    public function getVolume() { return $this->volume; }
    public function getPrice($qty)
    {
        return $this->close * $qty;
    }
    protected function loadQuotes($startDate, $endDate)
    {
        // load all the quotes for the period
        $this->loadedStartDate = $startDate;
        $this->loadedEndDate = $endDate;
        // zero the existing values
        unset($this->dates, $this->highData, $this->lowData);
        unset($this->openData, $this->closeData, $this->volData);
        unset($this->max, $this->min);
        $exch = $this->exch->getID();
        $symb = $this->symb;
        $query = "select date, high, low, open, close, volume from quotes where symb = '$symb' and exch = '$exch' and date >= '$startDate' and date <= '$endDate' order by date";
        foreach ($this->dbh->query($query) as $row)
        {
            $this->dates[] = $row['date'];
            $this->highData[] = $row['high'];
            $this->lowData[] = $row['low'];
            $this->openData[] = $row['open'];
            $this->closeData[] = $row['close'];
            $this->volData[] = $row['volume'];
            if ($row['high'] > $this->max)
            {
                $this->max = $row['high'];
                $this->maxDate = $row['date'];
            }
            if ($row['low'] < $this->min)
            {
                $this->min = $row['min'];
                $this->minDate = $row['date'];
            }
        }
    }
    public function getMin($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->min;
    }
    public function getMinDate($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->minDate;
    }
    public function getMax($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->max;
    }
    public function getMaxDate($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->maxDate;
    }
    public function getHighs($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->highData;
    }
    public function getLows($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->lowData;
    }
    public function getOpens($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->openData;
    }
    public function getCloses($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->closeData;
    }
    public function getVolumes($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->volData;
    }
    public function getDates($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->dates;
    }
}

class holding extends quote
{
    protected $hid, $buyPrice, $workingDate, $pfid, $openDate, $qty, $comment;
    public function __construct($symb, $portfolio)
    {
        // setup the the parent class (db connection etc)
        $workingDate = $portfolio->getWorkingDate();
        $exch = $portfolio->exch->getID();
        $pfid = $portfolio->getID();
        $this->pfid = $pfid;
        parent::__construct($symb, $exch, $workingDate);
        // load the info from the stocks table
        $query = "select * from holdings where symb = '$symb' and pfid = '$pfid';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('holding:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: holding, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['symb']) and $row['symb'] == $symb)
        {
            $this->hid = $row['hid'];
            $this->pfid = $row['pfid'];
            $this->openDate = $row['date'];
            $this->price = $row['price'];
            $this->qty = $row['volume'];
            $this->comment = $row['comment'];
        }
    }
    public function getHid() { return $this->hid; }
    public function getPfid() { return $this->pfid; }
    public function getOpenDate() { return $this->openDate; }
    public function getPrice() { return $this->price; }
    public function getQty() { return $this->qty; }
    public function getComment() { return $this->comment; }
    public function getGain()
    {
        return ($this->price * abs($this->qty)) + ($this->qty * ($this->cose - $this->price));
    }
    public function getValue()
    {
        return ($this->price * abs($this->qty)) + ($this->qty * ($this->cose - $this->price));
    }
    public function getCost()
    {
        return ($this->price * abs($this->qty));
    }
    public function IsGain()
    {
        $gain = $this->getGain();
        if ( $gain < 0 )
        {
            return -1;
        }
        elseif ($gain == 0)
        {
            return 0;
        }
        else
        {
            return 1;
        }
    }
}

?>
