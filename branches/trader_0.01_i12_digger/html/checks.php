<?php
function redirect_login_pf()
{
    session_start();
    $login_page = "/login.php";
    $portfolio_page = "/portfolio_admin.php";
    if (! isset($_SESSION['username']))
    {
        if ( $_SERVER["REQUEST_URI"] != $login_page )
        {
            header("Location: $login_page");
            exit;
        }
    }
    elseif (! isset($_SESSION['pfid']))
    {
        if ( $_SERVER["REQUEST_URI"] != $portfolio_page )
        {
            header("Location: $portfolio_page");
            exit;
        }
    }
}
?>
