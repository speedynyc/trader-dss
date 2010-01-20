<?php
    session_start();
    if (! isset($_SESSION['username']))
    {
        if (isset($_POST['username']))
        {
            $username = $_POST['username'];
            $passwd = $_POST['passwd'];

            $conn=pg_connect("host=localhost dbname=trader user=postgres password=happy") or die(pg_last_error($conn));
            $query = "select uid, name, passwd from users where name = '$username' and passwd = md5('$passwd')";
            $result = pg_query($conn, $query);
            if (pg_num_rows($result) == 1 )
            {
                $_SESSION['username'] = pg_fetch_result($result, 0, 'name');
                $_SESSION['uid'] = pg_fetch_result($result, 0, 'uid');
                echo "Welcome " . $_SESSION['username'] . " uid " . $_SESSION['uid'] . "\n";
            }
        }
        else
        {
            include "login.html";
        }
    }
    else
    {
        echo "Welcome back" . $_SESSION['username'] . " uid " . $_SESSION['uid'] . "\n";
    }
?>
