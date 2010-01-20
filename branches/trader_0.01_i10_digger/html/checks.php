<?php
function redirect_login_pf()
{
    session_start();
    if (! isset($_SESSION['username']))
    {
        header("Location: /login.php");
        exit;
    }
    if (! isset($_SESSION['pfid']))
    {
        header("Location: /pf_admin.php");
        exit;
    }
}
?>
