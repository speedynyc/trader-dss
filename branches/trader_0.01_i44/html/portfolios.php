<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('portfolios');
// Load the HTML_QuickForm module
require 'HTML/QuickForm.php';
global $db_hostname, $db_database, $db_user, $db_password;

$create_pf_form = new HTML_QuickForm('add_portfolio');
$choose_pf_form = new HTML_QuickForm('choose_portfolio');

// setup the DB connection for use in this script
try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}

// function to check date validity
function validate_date($date) {
    return checkdate($date['M'], $date['d'], $date['Y']);
}

function validate_new_portfolio($desc)
{
    // check that this portfolio doesn't exist
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_desc = $pdo->quote($desc);
    $uid = $pdo->quote($_SESSION['uid']);
    $query = "select count(*) from portfolios where name = $pf_desc and uid = $uid;";
    $count = 0;
    foreach ($pdo->query($query) as $row)
    {
        $count = $row['count'];
    }
    return $count == 0;
}

function check_percent($name, $value)
{
    // check that we've got a number between 0 and 100
    return ($value >= 0 and $value <= 100);
}

function create_add_form()
{
    global $create_pf_form, $pdo;
    // Instantiate a new form
    // trim all whitespace
    $create_pf_form->applyFilter('__ALL__', 'trim');
    // Add a text box
    $create_pf_form->addElement('header', null, 'Add a portfolio');
    $create_pf_form->addElement('text', 'pf_desc', 'Enter Description:', array('size' => 50, 'maxlength' => 255));
    $create_pf_form->addRule('pf_desc','Please enter a portfolio description','required');
    $create_pf_form->addRule('pf_desc','That portfolio already exists','callback', 'validate_new_portfolio');
    $create_pf_form->addElement('text', 'parcel', 'Enter parcel size:', array('size' => 10, 'maxlength' => 10));
    $create_pf_form->addRule('parcel','Please enter a numeric parcel size','required');
    $create_pf_form->addRule('parcel','Please enter a numeric parcel size','numeric');
    $create_pf_form->addElement('text', 'opening', 'Enter Opening Balance:', array('size' => 10, 'maxlength' => 10));
    $create_pf_form->addRule('opening','Please enter a numeric balance','required');
    $create_pf_form->addRule('opening','Please enter a numeric balance','numeric');
    $create_pf_form->addElement('text', 'sell_stop', 'Set Sell stop limit:', array('size' => 10, 'maxlength' => 10));
    $create_pf_form->addRule('sell_stop','Please enter a numeric Sell Stop','required');
    $create_pf_form->addRule('sell_stop','Please enter a numeric Sell Stop','numeric');
    $create_pf_form->registerRule('valid_percent','function','check_percent');
    $create_pf_form->addRule('sell_stop','Please enter a percentage','valid_percent');
    $create_pf_form->addElement('checkbox', 'auto', 'Automatically sell on sell stop:');
    $create_pf_form->addElement('checkbox', 'hide', 'Hide Stock Names:');
    $create_pf_form->addElement('date', 'start_date', 'Start Date:', array('format' => 'dMY', 'minYear' => 2000, 'maxYear' => date('Y'))); 
    $create_pf_form->addRule('start_date','Not a valid date','callback', 'validate_date');
    $exchanges = $create_pf_form->addElement('select','exchange','Exchange:');
    $query = "select exch, name from exchange order by name";
    foreach ($pdo->query($query) as $row)
    {
        $exchanges->addOption($row['name'], $row['exch']);
    }
    // Add a submit button
    $create_pf_form->addElement('submit','save','Create Portfolio');
}

function choose_portfolio($form)
{
    global $pdo;
    $pfid = $form['portfolio'];
    $_SESSION['pfid'] = $pfid;
    $_SESSION['hide'] = get_pf_hide_names($pfid);
}

// create a new portfolio
function create_portfolio($form)
{
    # extract and clean all the data
    global $pdo;
    $pf_desc = $pdo->quote($form['pf_desc']);
    $uid = $_SESSION['uid'];
    $exchange = new exchange($form['exchange']);
    $exch = $exchange->getExch();
    $parcel = $pdo->quote($form['parcel']);
    $start_date = sprintf("%04d-%02d-%02d", $form['start_date']['Y'], $form['start_date']['M'], $form['start_date']['d']);
    $opening_balance = $pdo->quote($form['opening']);
    if (isset($form['hide']))
    {
        $hide = 't';
    }
    else
    {
        $hide = 'f';
    }
    if (isset($form['auto']))
    {
        $auto = 't';
    }
    else
    {
        $auto = 'f';
    }
    $sell_stop = $form['sell_stop'];
    $start_date = $exchange->nearest_trade_day($start_date);
    // need to create the portfolio and add the first entry into summary as a transaction so that if one fails all do
    try{
        $pdo->beginTransaction();
        $query = "insert into portfolios (name, uid, exch, parcel, working_date, hide_names, sell_stop, auto_sell_stop) values ($pf_desc, '$uid', '$exch', $parcel, '$start_date', '$hide', '$sell_stop', '$auto');";
        $pdo->exec($query);
        $query = "select pfid from portfolios where uid = '$uid' and name = $pf_desc and exch = '$exch';";
        foreach ($pdo->query($query) as $row)
        {
            $pf_id = $pdo->quote($row['pfid']);
        }
        $query = "insert into pf_summary (pfid, date, cash_in_hand, holdings) values ($pf_id, '$start_date', $opening_balance, 0);";
        $pdo->exec($query);
        $pdo->commit();
    }
    catch (PDOException $e)
    {
        $pdo->rollBack();
        tr_warn('create_portfolio:' . $query . ':' . $e->getMessage());
    }
}

function delete_portfolio($pfid)
{
    global $pdo;
    try{
        $pdo->beginTransaction();
        $query = "delete from watch where pfid = '$pfid';";
        $pdo->exec($query);
        $query = "delete from cart where pfid = '$pfid';";
        $pdo->exec($query);
        $query = "delete from holdings where pfid = '$pfid';";
        $pdo->exec($query);
        $query = "delete from trades where pfid = '$pfid';";
        $pdo->exec($query);
        $query = "delete from pf_summary where pfid = '$pfid';";
        $pdo->exec($query);
        $query = "delete from portfolios where pfid = '$pfid';";
        $pdo->exec($query);
        $pdo->commit();
    }
    catch (PDOException $e)
    {
        tr_warn('delete_portfolio:' . $query . ':' . $e->getMessage());
        $pdo->rollBack();
    }
}

function create_choose_form()
{
    // Instantiate a new form tp choose the portfolio to work with
    global $choose_pf_form, $pdo;
    $choose_pf_form->addElement('header', null, 'Choose a portfolio');
    $uid = $pdo->quote($_SESSION['uid']);
    $query = "select pfid, name, exch, parcel, working_date from portfolios where uid = $uid order by name;";
    $first_row = true;
    foreach ($pdo->query($query) as $row)
    {

        $pf_id = $row['pfid'];
        $portfolio = new portfolio($row['pfid']);
        $pf_desc = $portfolio->getName();
        $exch = $portfolio->getExch()->getID();
        $exch_name = $portfolio->getExch()->getName();
        $pf_parcel = $portfolio->getParcel();
        $pf_working_date = $portfolio->getWorkingDate();
        $pf_start_date = $pf_working_date;
        //$exch = new exchange($row['exch']);
        //$exch_name = $exch->getName();
        //$pf_parcel = $row['parcel'];
        //$pf_start_date = get_pf_start_date($pf_id);
        //$pf_working_date = $row['working_date'];
        if ($first_row)
        {
            $choose_pf_form->addElement('radio','portfolio','Portfolios:',"$pf_desc<td>$exch_name</td> <td>$pf_parcel</td> <td>$pf_start_date</td> <td>$pf_working_date</td>",$pf_id);
            $choose_pf_form->addRule('portfolio','You must select a portfolio to trade','required');
            $first_row = false;
        }
        else
        {
            $choose_pf_form->addElement('radio','portfolio',null,"$pf_desc<td>$exch_name</td> <td>$pf_parcel</td> <td>$pf_start_date</td> <td>$pf_working_date</td>",$pf_id);
        }
    }
    $choose_pf_form->addElement('submit','choose','Trade with Portfolio');
    $choose_pf_form->addElement('submit','delete','Delete Portfolio', 'onclick="return confirm(\'Delete portfolio\nAre you Sure?\')"');
}

// this isn't good enough. The forms need to be instanciated here to process the values,
// but then they're out of date after processing
create_add_form();
create_choose_form();

// Validate an process or display
if (isset($_POST['save']))
{
    if ($create_pf_form->validate())
    {
        $create_pf_form->process('create_portfolio');
        // we've added a portfolio, load a clean create_pf_form and reload the choose
        $create_pf_form = new HTML_QuickForm('add_portfolio');
        create_add_form();
        $choose_pf_form = new HTML_QuickForm('choose_portfolio');
        create_choose_form();
    }
}
elseif (isset($_POST['choose']))
{
    if ($choose_pf_form->validate())
    {
        $choose_pf_form->process('choose_portfolio');
        header("Location: /queries.php");
        exit;
    }
}
elseif (isset($_POST['delete']))
{
    if ($choose_pf_form->validate())
    {
        $data = $choose_pf_form->exportValues();
        $pfid = $data['portfolio'];
        delete_portfolio($pfid);
        // recreate the choose form since the data has changed
        $choose_pf_form = new HTML_QuickForm('choose_portfolio');
        create_choose_form();
    }
}

// display the forms
print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td>';
$create_pf_form->display();
print '</td></tr>';
print '<tr><td>';
$choose_pf_form->display();
print '</td></tr></table>';

?>
