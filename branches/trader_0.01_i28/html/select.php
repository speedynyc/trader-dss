<?php
include("checks.php");
redirect_login_pf();
draw_trader_header('select');
// Load the HTML_QuickForm module
require 'HTML/QuickForm.php';
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pfid = $_SESSION['pfid'];
$pfname = get_pf_name($pfid);
$pf_working_date = get_pf_working_date($pfid);
$pf_exch = get_pf_exch($pfid);


$sql_input_form = new HTML_QuickForm('sql_input');
$sql_input_form->applyFilter('__ALL__', 'trim');
$sql_input_form->addElement('header', null, "SQL to select stocks for '$pfname' working date $pf_working_date");
$sql_input_form->addElement('textarea', 'sql_select', 'select:', 'wrap="soft" rows="3" cols="50"');
$sql_input_form->addRule('sql_select','Must select columns','required');
$sql_input_form->addElement('textarea', 'sql_from', 'from:', 'wrap="soft" rows="1" cols="50"');
$sql_input_form->addRule('sql_from','Must select tables','required');
$sql_input_form->addElement('textarea', 'sql_where', 'where:', 'wrap="soft" rows="4" cols="50"');
$sql_input_form->addRule('sql_where','Must include where clause','required');
$sql_input_form->addElement('textarea', 'sql_order', 'order by:', 'wrap="soft" rows="1" cols="50"');
$sql_input_form->addRule('sql_order','Must order the output','required');
$direction = $sql_input_form->addElement('select','sql_order_dir','direction:');
$direction->addOption('ascending', 'asc');
$direction->addOption('descending', 'desc');
$limit = $sql_input_form->addElement('select','sql_limit','limit:');
$limit->addOption('1', '1');
$limit->addOption('10', '10');
$limit->addOption('100', '100');
$chart_period = $sql_input_form->addElement('select','chart_period','Chart Period:');
$chart_period->addOption('1 week', '7');
$chart_period->addOption('1 month', '30');
$chart_period->addOption('2 months', '60');
$chart_period->addOption('3 months', '90');
$chart_period->addOption('6 months', '180');
$chart_period->addOption('1 year', '365');
$chart_period->addOption('2 years', '7305');
$chart_period->addOption('5 years', '1825');
$chart_period->addOption('10 years', '3650');
$sql_input_form->addElement('checkbox', 'chart', 'Draw Charts');
$sql_input_form->addElement('submit','execute_sql','Run Query');
if (isset($_SESSION['sql_select']))
{
    $sql_input_form->setDefaults(array(
                'sql_select' => $_SESSION['sql_select'],
                'sql_from'   => $_SESSION['sql_from'],
                'sql_where'  => $_SESSION['sql_where'],
                'sql_order'  => $_SESSION['sql_order'],
                'sql_order_dir' => $_SESSION['sql_order_dir'],
                'sql_limit'  => $_SESSION['sql_limit'],
                'chart_period' => $_SESSION['chart_period']));
}
else
{
    $sql_input_form->setDefaults(array(
                'sql_limit'  => 10,
                'sql_order_dir' => 'desc',
                'chart_period' => 180));
}

if (isset($_POST['execute_sql']))
{
    if ($sql_input_form->validate())
    {
        print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td align="center">';
        $sql_input_form->display();
        print '</td></tr>';
        // run the sql and return the results
        $data = $sql_input_form->exportValues();
        $_SESSION['sql_select']    = $sql_select = $data['sql_select'];
        $_SESSION['sql_from']      = $sql_from   = $data['sql_from'];
        $_SESSION['sql_where']     = $sql_where  = $data['sql_where'];
        $_SESSION['sql_order']     = $sql_order  = $data['sql_order'];
        $_SESSION['sql_order_dir'] = $sql_order_dir = $data['sql_order_dir'];
        $_SESSION['sql_limit']     = $sql_limit  = $data['sql_limit'];
        $_SESSION['chart_period']  = $chart_period = $data['chart_period'];
        $query = "select $sql_select from $sql_from where ($sql_where) and (quotes.date = '$pf_working_date' and quotes.exch = '$pf_exch') order by $sql_order $sql_order_dir limit $sql_limit;";
        try {
            $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        } catch (PDOException $e) {
            die("ERROR: Cannot connect: " . $e->getMessage());
        }
        print '<tr>';
        print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr>';
        print '<td><b>SQL</b></td>';
        print "<td>$query</td></tr></table>\n";
        /*
        print '<tr><td>';
        print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr>';
        print '<td><b>Added</b></td><td><b>Entered</b></td><td><b>Added</b></td></tr><tr>';
        print "<td>select</td><td>$sql_select</td><td>.</td></tr>\n";
        print "<tr><td>from</td><td>$sql_from</td><td>.</td></tr>\n";
        print "<tr><td>where</td><td>$sql_where</td><td>and (quotes.date = '$pf_working_date' and quotes.exch = '$pf_exch')</td></tr>\n";
        print "<tr><td>order by</td><td>$sql_order</td><td>$sql_order_dir</td></tr>\n";
        print "<tr><td>limit</td><td>$sql_limit</td><td>;</td></tr>\n";
        print "</table>\n";
        print '</td></tr>';
        */
        print '<tr><td>';
        $first = true;
        print '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="stocks" id="stocks">';
        print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr>';
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try 
        {
            $result = $pdo->query($query);
            while ($row = $result->fetch(PDO::FETCH_ASSOC))
            {
                if ($first)
                {
                    // work out the index names and print them as headers
                    $headers = array_keys($row);
                    $first = false;
                    print "<td>symb\n";
                    print '<input name="add_to_cart" value="Put into carts" type="submit" /></td>';
                    foreach ($headers as $index)
                    {
                        if ($index != 'symb')
                        {
                            print "<td>$index</td>\n";
                        }
                    }
                    if (isset($_POST['chart']))
                    {
                        print "<td>Chart</td>\n";
                    }
                    else
                    {
                        print '</tr>';
                    }
                }
                print "<tr>";
                // setup the first row of the table with the symbol details and the chance to add it to the cart or watch list
                if (! isset($row['symb']))
                {
                    die('<tr><td><font color="red">The Query must include the field "symb"</font></td></tr></table>');
                }
                else
                {
                    $symbol = $row['symb'];
                    $name = get_symb_name($symbol, $pf_exch);
                    print '<tr><td><table border="0" cellpadding="0" cellspacing="0" align="left"><tr>';
                    print "<td>$symbol: $name</td></tr>";
                    if (is_in_cart('cart', $symbol))
                    {
                        print "<tr><td>Buying:</td></tr>\n";
                    }
                    else
                    {
                        print "<tr><td><input type=\"checkbox\" name=\"buy[]\" value=\"$symbol\">Buy</td></tr>\n";
                    }
                    if (is_in_cart('watch', $symbol))
                    {
                        print "<tr><td>Watching:</td></tr>\n";
                    }
                    else
                    {
                        print "<tr><td><input type=\"checkbox\" name=\"watch[]\" value=\"$symbol\">Watch</td></tr>\n";
                    }
                    print '</table>';
                    // print the rest of the selected rows into the table
                    foreach ($headers as $index)
                    {
                        if ($index != 'symb')
                        {
                            print "<td>$row[$index]</td>\n";
                        }
                    }
                    if (isset($_POST['chart']))
                    {
                        print "<td><img SRC=\"/cgi-bin/chartstock.php?TickerSymbol=$symbol&TimeRange=$chart_period&working_date=$pf_working_date&exch=$pf_exch&ChartSize=M&Volume=1&VGrid=1&HGrid=1&LogScale=0&ChartType=OHLC&Band=None&avgType1=SMA&movAvg1=10&avgType2=SMA&movAvg2=25&Indicator1=RSI&Indicator2=MACD&Indicator3=WilliamR&Indicator4=TRIX&Button1=Update%20Chart\" ALIGN=\"bottom\" BORDER=\"0\"></td>";
                    }
                    print "</tr>\n";
                }
            }
            print '</form>';
        }
        catch (PDOException $e)
        {
            print('<tr><td><font color="red">' . $e->getMessage() . '</font></td></tr>');
        }
        print '</table>';
        print '</td></tr>';
        print '</table>';
    }
    else
    {
        print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td align="center">';
        $sql_input_form->display();
        print '</td></tr>';
        print '</table>';
    }
}
elseif (isset($_POST['add_to_cart']))
{
    print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td align="center">';
    $sql_input_form->display();
    print '</td></tr>';
    print '</table>';
    // add them to the shopping cart or watch list
    print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td align="center">';
    if (isset($_POST['buy']))
    {
        $buy = $_POST['buy'];
        foreach ($buy as $symbol)
        {
            if (add_to_cart('cart', $symbol))
            {
                print "<tr><td>Added $symbol to buy</td></td>";
            }
        }
    }
    print '</tr>';
    if (isset($_POST['watch']))
    {
        $watch = $_POST['watch'];
        foreach ($watch as $symbol)
        {
            if (add_to_cart('watch', $symbol))
            {
                print "<tr><td>Added $symbol to watch</td></td>";
            }
        }
    }
    print '</tr>';
    print '</table>';
}
else
{
    print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td align="center">';
    $sql_input_form->display();
    print '</td></tr>';
    print '</table>';
}

?>
