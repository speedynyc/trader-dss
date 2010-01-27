<?php
// This script displays the login screen, sets the session cookie with the username and uid then redirects to the profile admin page
require 'HTML/QuickForm.php';
@include("checks.php");
session_start();

function check_account($v)
{
    // this function is called with the username and password in the array $v
    // validate the account details from the database
    global $g_username, $g_uid;
    global $db_hostname, $db_database, $db_user, $db_password;
    $flag = false;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        #$pdo = new PDO("pgsql:host=localhost;dbname=trader", "postgres", "happy");
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

if (isset($_SESSION['username']))
{
    // save the username to use as the defualt and then wipe the cookie clean
    $default_username = $_SESSION['username'];
    #unset($_SESSION['uid']);
    #unset($_SESSION['username']);
    #unset($_SESSION['pfid']);
}

# create the form and validation rules
$login_form = new HTML_QuickForm('login');
$login_form->applyFilter('__ALL__', 'trim');
$login_form->addElement('header', null, 'Login to the <a href="http://code.google.com/p/trader-dss/">Trader DSS</a>. Authorised users only');
$login_form->addElement('text', 'username', 'Username:', array('size' => 30, 'maxlength' => 100));
$login_form->addRule('username', 'Please enter your username', 'required');
$login_form->addElement('password', 'passwd', 'Password:', array('size' => 10, 'maxlength' => 100));
$login_form->addRule(array('username', 'passwd'), 'Account details incorrect', 'callback', 'check_account');
$login_form->addRule('passwd', 'Must enter a password', 'required');
$login_form->addElement('submit', 'login', 'Login');
if (isset($default_username))
{
    $login_form->setDefaults(array('username' => $default_username));
}
$g_username = ''; # global to hold the username
$g_uid = ''; # global to hold the uid
if ($login_form->validate())
{
    $_SESSION['username'] = $g_username;
    $_SESSION['uid'] = $g_uid;
    unset($_SESSION['pfid']);
    redirect_login_pf();
}
else
{
    draw_trader_header('login',false);
    print '<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td>';
    $login_form->display();
    print '</td></tr></table>';
}
?>
