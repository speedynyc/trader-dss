<?php
    @include("checks.php");
    redirect_login_pf();
    // Load the HTML_QuickForm module
    require 'HTML/QuickForm.php';
    // Instantiate a new form
    $form = new HTML_QuickForm('add_portfolio');
    // Add a text box
    $form->addElement('header', null, 'Add or select portfolio');
    $form->addElement('text', 'pf_desc', 'Enter Description:', array('size' => 50, 'maxlength' => 255));
    $form->addRule('pf_desc','Please enter a portfolio description','required');
    $form->addElement('text', 'parcel', 'Enter parcel size:', array('size' => 10, 'maxlength' => 10));
    $form->addRule('parcel','Please enter a numeric parcel size','required');
    $form->addRule('parcel','Please enter a numeric parcel size','numeric');
    $form->addElement('date', 'start_date', 'Start Date:', array('format' => 'dMY', 'minYear' => 2000, 'maxYear' => date('Y'))); 
    #$exchanges = array('L' => 'London');
    //$exchanges = array('London');
    //$form->addElement('select','exchange','Exchange: ',$exchanges);
    $exchanges = $form->addElement('select','exchange','Exchange:');
    $exchanges->addOption('London', 'L');
    // Add a submit button
    $form->addElement('submit','save','Create Portfolio');
    $form->addElement('reset', null, 'Reset Form');
    // Add a validation rule: title is required
    // Call the processing function if the submitted form
    // data is valid; otherwise, display the form
    if ($form->validate()) {
        $form->process('create_portfolio');
    } else {
        $form->display();
    }
    // Define a function to process the form data
    function create_portfolio($v) {
        global $exchanges;
        // Entity-encode any special characters in $v['title']
        $v['pf_desc'] = htmlentities($v['pf_desc']);
        print "insert into portfolios set name = '" . $v['pf_desc'] . "', uid = '" . $_SESSION['uid'] . "', exch = '" . $exchanges[$v['exchange']] . "', parcel = '" . $v['parcel'] . "', start_date = '" . $v['start_date'] . "', working_date = '" . $v['start_date'] . "';\n" ;
        print "insert into portfolios set name = '" . $v['pf_desc'] . "', uid = '" . $_SESSION['uid'] . "', exch = '" . $v['exchange'] . "', parcel = '" . $v['parcel'] . "', start_date = '" . $v['start_date'] . "', working_date = '" . $v['start_date'] . "';\n" ;
    }
?>
