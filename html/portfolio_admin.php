<?php
@include("checks.php");
redirect_login_pf();
// Load the HTML_QuickForm module
require 'HTML/QuickForm.php';

// setup the DB connection for use in this script
try {
    $pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}

// function to check date validity
function validate_date($v) {
    return checkdate($v['M'], $v['d'], $v['Y']);
}

function validate_new_portfolio($v)
{
    # check that this portfolio doesn't exist
    global $pdo;
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

// Instantiate a new form
$create_pf_form = new HTML_QuickForm('add_portfolio');
# trim all whitespace
$create_pf_form->applyFilter('__ALL__', 'trim');
// Add a text box
$create_pf_form->addElement('header', null, 'Add or select portfolio');
$create_pf_form->addElement('text', 'pf_desc', 'Enter Description:', array('size' => 50, 'maxlength' => 255));
$create_pf_form->addRule('pf_desc','Please enter a portfolio description','required');
$create_pf_form->addRule('pf_desc','That portfolio already exists','callback', 'validate_new_portfolio');
$create_pf_form->addElement('text', 'parcel', 'Enter parcel size:', array('size' => 10, 'maxlength' => 10));
$create_pf_form->addRule('parcel','Please enter a numeric parcel size','required');
$create_pf_form->addRule('parcel','Please enter a numeric parcel size','numeric');
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
//$create_pf_form->addElement('reset', null, 'Reset Form');
// Validate an process or display
if (isset($_POST['save']))
{
    if ($create_pf_form->validate())
    {
        $create_pf_form->process('create_portfolio');
        # even though we've saved the results we draw the form for another
        $create_pf_form->display();
    }
}
else
{
    $create_pf_form->display();
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
    $query = "select date from trade_dates where date >= $start_date order by date asc limit 1;";
    foreach ($pdo->query($query) as $row)
    {
        $start_date = $pdo->quote($row['date']);
    }
    $pdo->exec("insert into portfolios (name, uid, exch, parcel, start_date, working_date) values ($pf_desc, $uid, $exchange, $parcel, $start_date, $start_date);");
    redirect_login_pf();
}

print "<hr>\n";

// Instantiate a new form
$choose_pf_form = new HTML_QuickForm('choose_portfolio');
// Add a text box
$uid = $pdo->quote($_SESSION['uid']);
$query = "select pfid, name, exch, parcel, start_date, working_date from portfolios where uid = $uid order by name;";
$first_row = true;
foreach ($pdo->query($query) as $row)
{
    $pf_id = $row['pfid'];
    $pf_desc = $row['name'];
    $pf_exch = $row['exch'];
    $pf_parcel = $row['parcel'];
    $pf_start_date = $row['start_date'];
    $pf_working_date = $row['working_date'];
    if ($first_row)
    {
        $choose_pf_form->addElement('radio','portfolio','Portfolios:',"$pf_desc: $pf_exch: $pf_parcel: $pf_start_date: $pf_working_date",$pf_id);
        $first_row = false;
    }
    else
    {
        $choose_pf_form->addElement('radio','portfolio',null,"$pf_desc: $pf_exch: $pf_parcel: $pf_start_date: $pf_working_date",$pf_id);
    }
}
$choose_pf_form->addElement('submit','choose','Trade!');
if (isset($_POST['choose']))
{
    if ($choose_pf_form->validate())
    {
        $data = $choose_pf_form->exportValues();
        $pfid = $data['portfolio'];
        $_SESSION['pfid'] = $pfid;
        header("Location: /trade.php");
        exit;
    }
    else
    {
        $choose_pf_form->display();
    }
}
else
{
    $choose_pf_form->display();
}
?>
