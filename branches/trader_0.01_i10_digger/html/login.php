<?php
// This script displays the login screen, sets the session cookie with the username and uid then redirects to the profile admin page
require 'HTML/QuickForm.php';
@include("checks.php");
redirect_login_pf();    # start session and redirect depending on cookie values

function check_account($v)
{
    // this function is called with the username and password in the array $v
    // validate the account details from the database
    global $g_username;
    global $g_uid;
    $flag = false;
    try {
        $pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $username = $pdo->quote($v[0]);
    $passwd = $pdo->quote($v[1]);
    $query = "select uid, name, passwd from users where name = $username and passwd = md5($passwd)";
    foreach ($pdo->query($query) as $row)
    {
        $g_username = $row['name'];
        $g_uid = $row['uid'];
        $flag = true;
    }
    unset($pdh);
    return $flag;
}

# create the form and validation rules
$form = new HTML_QuickForm('login');
$form->addElement('header', null, 'Login to the Trader DSS. Authorised users only');
$form->addElement('text', 'username', 'Username:', array('size' => 30, 'maxlength' => 100));
$form->addRule('username', 'Please enter your username', 'required');
$form->addElement('password', 'passwd', 'Password:', array('size' => 10, 'maxlength' => 100));
$form->addRule(array('username', 'passwd'), 'Account details incorrect', 'callback', 'check_account');
$form->addRule('passwd', 'Must enter a password', 'required');
$form->addElement('submit', 'login', 'Login');
$g_username = ''; # global to hold the username
$g_uid = ''; # global to hold the uid
if ($form->validate())
{
    $form->freeze();
    $_SESSION['username'] = $g_username;
    $_SESSION['uid'] = $g_uid;
    redirect_login_pf();
}
else
{
    $form->display();
}
?>
