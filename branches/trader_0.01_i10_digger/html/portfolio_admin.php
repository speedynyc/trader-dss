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
$form = new HTML_QuickForm('add_portfolio');
// Add a text box
$form->addElement('header', null, 'Add or select portfolio');
$form->addElement('text', 'pf_desc', 'Enter Description:', array('size' => 50, 'maxlength' => 255));
$form->addRule('pf_desc','Please enter a portfolio description','required');
$form->addRule('pf_desc','That portfolio already exists','callback', 'validate_new_portfolio');
$form->addElement('text', 'parcel', 'Enter parcel size:', array('size' => 10, 'maxlength' => 10));
$form->addRule('parcel','Please enter a numeric parcel size','required');
$form->addRule('parcel','Please enter a numeric parcel size','numeric');
$form->addElement('date', 'start_date', 'Start Date:', array('format' => 'dMY', 'minYear' => 2000, 'maxYear' => date('Y'))); 
$form->addRule('start_date','Not a valid date','callback', 'validate_date');
$exchanges = $form->addElement('select','exchange','Exchange:');
$query = "select exch, name from exchange order by name";
foreach ($pdo->query($query) as $row)
{
    $exchanges->addOption($row['name'], $row['exch']);
}

// Add a submit button
$form->addElement('submit','save','Create Portfolio');
$form->addElement('reset', null, 'Reset Form');
// Validate an process or display
if ($form->validate()) {
    $form->process('create_portfolio');
} else {
    $form->display();
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
    $working_date = $start_date;

    print "insert into portfolios (name, uid, exch, parcel, start_date, working_date) values ($pf_desc, $uid, $exchange, $parcel, $start_date, $start_date);\n" ;
}
?>
