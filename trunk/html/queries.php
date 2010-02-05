<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('queries');
// Load the HTML_QuickForm module
require 'HTML/QuickForm.php';
global $db_hostname, $db_database, $db_user, $db_password;

$username = $_SESSION['username'];
$uid = $_SESSION['uid'];
$pfid = $_SESSION['pfid'];
$pfname = get_pf_name($pfid);
$pf_working_date = get_pf_working_date($pfid);
$pf_exch = get_pf_exch($pfid);

if (isset($_SESSION['qid']))
{
    $q_id = $_SESSION['qid'];
}

try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    #$pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}

$select_query_form = new HTML_QuickForm('select_query');
$sql_input_form = new HTML_QuickForm('sql_input');

function create_select_query_form()
{
    global $db_hostname, $db_database, $db_user, $db_password, $pdo, $uid;
    global $select_query_form, $q_id;
    $query = "select count(*) as count from queries where uid = '$uid';";
    $result = $pdo->query($query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row['count'] > 0)
    {
        $first = true;
        $query = "select qid, name from queries where uid = $uid order by name;";
        $result = $pdo->query($query);
        $choose_query = $select_query_form->addElement('select','choose_query','Select Query to Edit:');
        while ($row = $result->fetch(PDO::FETCH_ASSOC))
        {
            $q_name = $row['name'];
            $q_qid = $row['qid'];
            if (isset($q_id) and $q_qid == $q_id)
            {
                $choose_query->addOption($q_name, $q_qid, "selected");
            }
            else
            {
                $choose_query->addOption($q_name, $q_qid);
            }
        }
        $select_query_form->addElement('submit','edit_query','Load Query');
    }
}

function create_sql_input_form()
{
    global $sql_input_form, $q_id;
    $sql_input_form->applyFilter('__ALL__', 'trim');
    $sql_input_form->addElement('header', null, "Save queries for later use.");
    $sql_input_form->addElement('textarea', 'sql_name', 'Query Description:', 'wrap="soft" rows="1" cols="50"');
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
    $sql_input_form->addElement('submit','use_sql','Use Query');
    $sql_input_form->addElement('submit','save_sql','Save Query');
    if (isset($q_id))
    {
        $sql_input_form->addElement('submit','del_sql',"Delete Query $q_id");
        $sql_input_form->setDefaults(array(
                    'sql_name' => $_SESSION['sql_name'],
                    'sql_select' => $_SESSION['sql_select'],
                    'sql_from'   => $_SESSION['sql_from'],
                    'sql_where'  => $_SESSION['sql_where'],
                    'sql_order'  => $_SESSION['sql_order'],
                    'sql_order_dir' => $_SESSION['sql_order_dir'],
                    'chart_period' => $_SESSION['chart_period'],
                    'sql_limit'  => $_SESSION['sql_limit']));
    }
    else
    {
        $sql_input_form->setDefaults(array(
                    'sql_limit'  => 10,
                    'sql_order_dir' => 'desc',
                    'chart_period' => 180));
    }
}

create_select_query_form();
create_sql_input_form();

if (isset($_POST['edit_query']))
{
    $data = $select_query_form->exportValues();
    $q_id = $data['choose_query'];
    $_SESSION['qid'] = $q_id;
    $query = "select * from queries where qid = $q_id;";
    $result = $pdo->query($query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $_SESSION['sql_name']      = $sql_name = $row['name'];
    $_SESSION['sql_select']    = $sql_select = $row['sql_select'];
    $_SESSION['sql_from']      = $sql_from   = $row['sql_from'];
    $_SESSION['sql_where']     = $sql_where  = $row['sql_where'];
    $_SESSION['sql_order']     = $sql_order  = $row['sql_order'];
    $_SESSION['sql_order_dir'] = $sql_order_dir = $row['sql_order_dir'];
    $_SESSION['sql_limit']     = $sql_limit  = $row['sql_limit'];
    $_SESSION['chart_period']  = $chart_period = $row['chart_period'];
    $_SESSION['qid']           = $q_id;
    $sql_input_form = new HTML_QuickForm('sql_input');
    create_sql_input_form();
}
if (isset($_POST['use_sql']))
{
    if ($sql_input_form->validate())
    {
        $data = $sql_input_form->exportValues();
        $_SESSION['sql_name']      = $sql_name = $data['sql_name'];
        $_SESSION['sql_select']    = $sql_select = $data['sql_select'];
        $_SESSION['sql_from']      = $sql_from   = $data['sql_from'];
        $_SESSION['sql_where']     = $sql_where  = $data['sql_where'];
        $_SESSION['sql_order']     = $sql_order  = $data['sql_order'];
        $_SESSION['sql_order_dir'] = $sql_order_dir = $data['sql_order_dir'];
        $_SESSION['sql_limit']     = $sql_limit  = $data['sql_limit'];
        $_SESSION['chart_period']  = $chart_period = $data['chart_period'];
        header("Location: /select.php");
        exit;
    }
}
if (isset($_POST['save_sql']))
{
    if ($sql_input_form->validate())
    {
        $data = $sql_input_form->exportValues();
        $_SESSION['sql_name']      = $sql_name = $data['sql_name'];
        $_SESSION['sql_select']    = $sql_select = $data['sql_select'];
        $_SESSION['sql_from']      = $sql_from   = $data['sql_from'];
        $_SESSION['sql_where']     = $sql_where  = $data['sql_where'];
        $_SESSION['sql_order']     = $sql_order  = $data['sql_order'];
        $_SESSION['sql_order_dir'] = $sql_order_dir = $data['sql_order_dir'];
        $_SESSION['sql_limit']     = $sql_limit  = $data['sql_limit'];
        $_SESSION['chart_period']  = $chart_period = $data['chart_period'];
        // do we have a qid? if so save these to that
        $query = "insert into queries (uid, name, sql_select, sql_from, sql_where, sql_order, sql_order_dir, sql_limit, chart_period) values ('$uid', '$sql_name', '$sql_select', '$sql_from', '$sql_where', '$sql_order', '$sql_order_dir', '$sql_limit', '$chart_period');";
        $pdo->exec($query);
        // changed both forms, so reload them
        $sql_input_form = new HTML_QuickForm('sql_input');
        create_sql_input_form();
        $select_query_form = new HTML_QuickForm('select_query');
        create_select_query_form();
    }
}
if (isset($_POST['del_sql']))
{
    $query = "delete from queries where qid = $q_id;";
    $pdo->exec($query);
    $select_query_form = new HTML_QuickForm('select_query');
    create_select_query_form();
}


print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
print '<tr><td>';
$select_query_form->display();
print "</td></tr>\n";
print '<tr><td>';
$sql_input_form->display();
print '</td></tr>';
print '</table>';

?>
