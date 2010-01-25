<?php
@include("checks.php");
redirect_login_pf();
// Load the HTML_QuickForm module
require 'HTML/QuickForm.php';

function get_pf_name($v)
{
    // setup the DB connection for use in this script
    try {
        $pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_id = $pdo->quote($v);
    $query = "select name from portfolios where pfid = $pf_id;";
    foreach ($pdo->query($query) as $row)
    {
        return $row['name'];
    }
    return 'Unknown Portfolio';
}

function get_pf_working_date($v)
{
    // setup the DB connection for use in this script
    try {
        $pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_id = $pdo->quote($v);
    $query = "select working_date from portfolios where pfid = $pf_id;";
    foreach ($pdo->query($query) as $row)
    {
        return $row['working_date'];
    }
    return '200-01-01';
}

function get_pf_exch($v)
{
    // setup the DB connection for use in this script
    try {
        $pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_id = $pdo->quote($v);
    $query = "select exch from portfolios where pfid = $pf_id;";
    foreach ($pdo->query($query) as $row)
    {
        return $row['exch'];
    }
    return 'X';
}

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pfid = $_SESSION['pfid'];
$pfname = get_pf_name($pfid);
$pf_working_date = get_pf_working_date($pfid);
$pf_exch = get_pf_exch($pfid);


#print "OK $username ($uid), Lets get to trading portfolio $pfid!\n";
$sql_input_form = new HTML_QuickForm('sql_input');
$sql_input_form->applyFilter('__ALL__', 'trim');
$sql_input_form->addElement('header', null, "SQL to select stocks for '$pfname' working date $pf_working_date");
$sql_input_form->addElement('textarea', 'sql_colums', 'select:', 'wrap="soft" rows="1" cols="50"');
$sql_input_form->addRule('sql_colums','Must select columns','required');
$sql_input_form->addElement('textarea', 'sql_from', 'from:', 'wrap="soft" rows="2" cols="50"');
$sql_input_form->addRule('sql_from','Must select tables','required');
$sql_input_form->addElement('textarea', 'sql_where', 'where:', 'wrap="soft" rows="3" cols="50"');
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
$sql_input_form->addElement('submit','execute_sql','Run Query');
if (isset($_POST['execute_sql']))
{
    if ($sql_input_form->validate())
    {
        $sql_input_form->display();
        // run the sql and return the results
        $data = $sql_input_form->exportValues();
        $sql_colums = $data['sql_colums'];
        $sql_from = $data['sql_from'];
        $sql_where = $data['sql_where'];
        $sql_order = $data['sql_order'];
        $sql_order_dir = $data['sql_order_dir'];
        $sql_limit = $data['sql_limit'];
        $query = "select $sql_colums from $sql_from where ($sql_where) and (quotes.date = '$pf_working_date' and quotes.exch = '$pf_exch') order by $sql_order $sql_order_dir limit $sql_limit;";
        try {
            $pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
        } catch (PDOException $e) {
            die("ERROR: Cannot connect: " . $e->getMessage());
        }
        print "executing '$query'";
#$stmt = $pdo->prepare($query);
        $first = true;
        print '<table border="1" cellpadding="5" cellspacing="0"><tr>';
        $result = $pdo->query($query);
        //$row = $result->fetch(PDO::FETCH_ASSOC);
        while ($row = $result->fetch(PDO::FETCH_ASSOC))
        {
            if ($first)
            {
                // work out the index names and print them as headers
                $headers = array_keys($row);
                $first = false;
                foreach ($headers as $index)
                {
                    print "<td>$index</td>";
                }
                print '</tr>';
            }
            print '<tr>';
            foreach ($headers as $index)
            {
                print "<td>$row[$index]</td>";
            }
            print '</td>';
        }
        print '</table>';
    }
    else
    {
        $sql_input_form->display();
    }
}
else
{
    $sql_input_form->display();
}
?>
