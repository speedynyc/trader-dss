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
function validate_date($v) {
    return checkdate($v['M'], $v['d'], $v['Y']);
}

function validate_new_portfolio($v)
{
    // check that this portfolio doesn't exist
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $pf_desc = $pdo->quote($v);
    $uid = $pdo->quote($_SESSION['uid']);
    $query = "select count(*) from portfolios where name = $pf_desc and uid = $uid;";
    $count = 0;
    foreach ($pdo->query($query) as $row)
    {
        $count = $row['count'];
    }
    return $count == 0;
}

function get_exch_desc($v)
{
    // return the description of the given exchange
    global $db_hostname, $db_database, $db_user, $db_password;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $exch = $pdo->quote($v);
    $query = "select name from exchange where exch = $exch";
    foreach ($pdo->query($query) as $row)
    {
        return $row['name'];
    }
    return 'Unknown Exchange';
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
// Validate an process or display
if (isset($_POST['save']))
{
    if ($create_pf_form->validate())
    {
        $create_pf_form->process('create_portfolio');
        // even though we've saved the results we draw the form for another
    }
}


// Define a function to process the form data
function create_portfolio($v)
{
    # extract and clean all the data
    global $pdo;
    $pf_desc = $pdo->quote($v['pf_desc']);
    $uid = $_SESSION['uid'];
    $exchange = $pdo->quote($v['exchange']);
    $parcel = $pdo->quote($v['parcel']);
    $start_date = sprintf("%04d-%02d-%02d", $v['start_date']['Y'], $v['start_date']['M'], $v['start_date']['d']);
    $start_date = $pdo->quote($start_date);
    $opening_balance = $pdo->quote($v['opening']);
    $query = "select date from trade_dates where date >= $start_date order by date asc limit 1;";
    foreach ($pdo->query($query) as $row)
    {
        $start_date = $pdo->quote($row['date']);
    }
    // need to create the portfolio and add the first entry into summary as a transaction so that if one fails all do
    try{
        $pdo->beginTransaction();
        $query = "insert into portfolios (name, uid, exch, parcel, start_date, working_date) values ($pf_desc, $uid, $exchange, $parcel, $start_date, $start_date);";
        $pdo->exec($query);
        $query = "select pfid from portfolios where uid = '$uid' and name = $pf_desc and exch = $exchange;";
        foreach ($pdo->query($query) as $row)
        {
            $pf_id = $pdo->quote($row['pfid']);
        }
        $query = "insert into pf_summary (pfid, date, cash_in_hand, holdings) values ($pf_id, $start_date, $opening_balance, 0);";
        $pdo->exec($query);
        $pdo->commit();
    }
    catch (PDOException $e)
    {
        $pdo->rollBack();
        tr_warn('create_portfolio:' . $query . ':' . $e->getMessage());
    }
    redirect_login_pf();
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
    $query = "select pfid, name, exch, parcel, start_date, working_date from portfolios where uid = $uid order by name;";
    $first_row = true;
    foreach ($pdo->query($query) as $row)
    {
        $pf_id = $row['pfid'];
        $pf_desc = $row['name'];
        $pf_exch = get_exch_desc($row['exch']);
        $pf_parcel = $row['parcel'];
        $pf_start_date = $row['start_date'];
        $pf_working_date = $row['working_date'];
        if ($first_row)
        {
            $choose_pf_form->addElement('radio','portfolio','Portfolios:',"$pf_desc<td>$pf_exch</td> <td>$pf_parcel</td> <td>$pf_start_date</td> <td>$pf_working_date</td>",$pf_id);
            $choose_pf_form->addRule('portfolio','You must select a portfolio to trade','required');
            $first_row = false;
        }
        else
        {
            $choose_pf_form->addElement('radio','portfolio',null,"$pf_desc<td>$pf_exch</td> <td>$pf_parcel</td> <td>$pf_start_date</td> <td>$pf_working_date</td>",$pf_id);
        }
    }
    $choose_pf_form->addElement('submit','choose','Select Shares');
    $choose_pf_form->addElement('submit','delete','Delete Portfolio');
}

// this isn't good enough. The forms need to be instanciated here to process the values,
// but then they're out of date after processing
create_add_form();
create_choose_form();

if (isset($_POST['choose']))
{
    if ($choose_pf_form->validate())
    {
        $data = $choose_pf_form->exportValues();
        $pfid = $data['portfolio'];
        $_SESSION['pfid'] = $pfid;
        header("Location: /select.php");
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
    }
}

// this is an ugly hack to re-create the forms after processing.
// I suspect that this means that quickform isn't good enough
$create_pf_form = new HTML_QuickForm('add_portfolio');
$choose_pf_form = new HTML_QuickForm('choose_portfolio');
create_add_form();
create_choose_form();

print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td>';
$create_pf_form->display();
print '</td></tr>';
print '<tr><td>';
$choose_pf_form->display();
print '</td></tr></table>';
?>
