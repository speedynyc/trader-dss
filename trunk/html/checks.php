<?php

$db_hostname = 'localhost';
$db_database = 'trader';
$db_user     = 'postgres';
$db_password = 'happy';

function tr_warn($message='No message!')
{
    print('<font color="red">' . $message . '</font>');
}

function update_cart($cart, $pfid, $pf_working_date)
{
    // this function is probably only going to be used by trade and watch so really shouldn't be here
    global $db_hostname, $db_database, $db_user, $db_password;
    $pf_working_date = get_pf_working_date($pfid);
    $exch = get_pf_exch($pfid);
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $query = "select * from $cart where date <= '$pf_working_date' and pfid = '$pfid';";
    foreach ($pdo->query($query) as $row)
    {
        $symb = $row['symb'];
        if (isset($_POST["buy_volume_$symb"]))
        {
            $volume = $_POST["buy_volume_$symb"];
            if (is_numeric($volume))
            {
                $update = "update $cart set volume = '$volume' where pfid = '$pfid' and date = '$pf_working_date' and symb = '$symb';";
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
        if (isset($_POST["buy_comment_$symb"]))
        {
            $comment = $_POST["buy_comment_$symb"];
            $update = "update $cart set comment = '$comment' where pfid = '$pfid' and date = '$pf_working_date' and symb = '$symb';";
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

function buy_stock($symb, $comment = '', $volume = 0)
{
    // adds all symbols in the given list to the given table
    global $db_hostname, $db_database, $db_user, $db_password;
    $pfid = $_SESSION['pfid'];
    $date = get_pf_working_date($pfid);
    $exch = get_pf_exch($pfid);
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
    // add the trade to the trades table
    $total = $qty * $close;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $query = "select cash_in_hand from pf_summary where pfid = '$pfid' and date = '$date';";
    foreach ($pdo->query($query) as $row)
    {
        $cash_in_hand = $row['cash_in_hand'];
    }
    $query = "select holdings from pf_summary where pfid = '$pfid' and date = '$date';";
    foreach ($pdo->query($query) as $row)
    {
        $holdings = $row['holdings'];
    }
    tr_warn("$cash_in_hand = $cash_in_hand - $total");
    $cash_in_hand = $cash_in_hand - $total;
    $holdings = $holdings + $total;
    try 
    {
        $pdo->beginTransaction();
        $query = "insert into trades (pfid, date, symb, price, volume, comment) values ('$pfid', '$date', '$symb', '$close', '$qty', '$comment');";
        $pdo->exec($query);
        // update the pf_summary with the trade
        $query = "update pf_summary set cash_in_hand = '$cash_in_hand', holdings = '$holdings' where date = '$date' and pfid = '$pfid';";
        $pdo->exec($query);
        $pdo->commit();
    }
    catch (PDOException $e)
    {
        tr_warn('buy_stock:' . $query . ':' . $e->getMessage());
        return false;
    }
    return true;
}

function add_to_cart($table, $symb, $comment = '', $volume = 0)
{
    // adds all symbols in the given list to the given table
    global $db_hostname, $db_database, $db_user, $db_password;
    $pfid = $_SESSION['pfid'];
    $date = get_pf_working_date($pfid);
    $name = get_pf_name($pfid);
    $exch = get_pf_exch($pfid);
    $close = get_stock_close($symb, $date, $exch);
    $parcel = get_pf_parcel_size($pfid);
    if ($comment == '')
    {
        tr_warn("$name: $date");
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
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    $date = get_pf_working_date($pfid);
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    $date = get_pf_working_date($pfid);
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

function redirect_login_pf()
{
    /* the idea here is to redirect to the login or porftolio selection page when 
        the cookie doesn't contain a valid username or portfolio.
        This will stop someone from opening a browser and getting some page other than the login page
    */
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

function draw_trader_header($active_page, $allow_others=true)
{
    // dray the header with a table linking the trader pages like tabs in a notebook
    // session infomation is used to communicate between the tabs
    $active_page = strtolower($active_page);
    $active_colour = 'white';
    $inactive_colour = 'grey';
    if (! isset($allow_others))
    {
        $allow_others = true;
    }
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
    switch ($active_page) {
        case 'login':
        case 'portfolios':
        case 'booty':
        case 'select':
        case 'trade':
        case 'watch':
        case 'queries':
            print '<table border="1" cellpadding="5" cellspacing="0" width="90%" align="center">';
            print '<tr>';
            break;
        default:
            print("[FATAL]Cannot create header, given $active_page\n");
            $active_page = 'login';
            break;
    }
    if ($active_page == 'login')
    {
        draw_cell($active_page, '/login.php', $active_colour, false);
    }
    else
    {
        // must always be possible to choose the login page
        draw_cell('login', '/login.php', $inactive_colour, true);
    }
    if ($active_page == 'portfolios')
    {
        draw_cell($active_page, '/portfolios.php', $active_colour, true);
    }
    else
    {
        draw_cell('portfolios', '/portfolios.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'booty')
    {
        draw_cell($active_page, '/booty.php', $active_colour, true);
    }
    else
    {
        draw_cell('booty', '/booty.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'select')
    {
        draw_cell($active_page, '/select.php', $active_colour, true);
    }
    else
    {
        draw_cell('select', '/select.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'trade')
    {
        draw_cell($active_page, '/trade.php', $active_colour, true);
    }
    else
    {
        draw_cell('trade', '/trade.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'watch')
    {
        draw_cell($active_page, '/watch.php', $active_colour, true);
    }
    else
    {
        draw_cell('watch', '/watch.php', $inactive_colour, $allow_others);
    }
    if ($active_page == 'queries')
    {
        draw_cell($active_page, '/queries.php', $active_colour, true);
    }
    else
    {
        draw_cell('queries', '/queries.php', $inactive_colour, $allow_others);
    }
    print "</tr></table>\n";
}

function get_pf_name($pfid)
{
    // setup the DB connection for use in this script
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_id = $pdo->quote($pfid);
    $query = "select name from portfolios where pfid = $pf_id;";
    foreach ($pdo->query($query) as $row)
    {
        return $row['name'];
    }
    return 'Unknown Portfolio';
}

function get_pf_exch($pfid)
{
    // setup the DB connection for use in this script
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_id = $pdo->quote($pfid);
    $query = "select exch from portfolios where pfid = $pf_id;";
    foreach ($pdo->query($query) as $row)
    {
        return $row['exch'];
    }
    return 'X';
}

function get_pf_parcel_size($pfid)
{
    // setup the DB connection for use in this script
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_id = $pdo->quote($pfid);
    $query = "select parcel from portfolios where pfid = $pf_id;";
    foreach ($pdo->query($query) as $row)
    {
        return $row['parcel'];
    }
    return '1';
}

function get_pf_working_date($pfid)
{
    // setup the DB connection for use in this script
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_id = $pdo->quote($pfid);
    $query = "select working_date from portfolios where pfid = $pf_id;";
    foreach ($pdo->query($query) as $row)
    {
        return $row['working_date'];
    }
    return '200-01-01';
}

function get_symb_name($symb, $exch)
{
    // retrieve any field from a table indexed on symb, date, exch
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select name from stocks where symb = '$symb' and exch = '$exch';";
    foreach ($pdo->query($query) as $row)
    {
        return $row['name'];
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
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select $field from $table where symb = '$symb' and date = '$date' and exch = '$exch';";
    foreach ($pdo->query($query) as $row)
    {
        return $row[$field];
    }
    return false;
}

?>
