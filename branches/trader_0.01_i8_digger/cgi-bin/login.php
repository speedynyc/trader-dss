<?php
    function authenticate_user() {
        header('WWW-Authenticate: Basic realm="trader"');
        header("HTTP:1. 401 Unauthorized");
        exit;
    }

    if (! isset($_SERVER['PHP_AUTH_USER'])) {
        authenticate_user();
    } else {
        $conn=pg_connect("host=localhost dbname=trader user=postgres password=happy") or die(pg_last_error($conn));
        $query = "select uid, name, passwd from users where name = '$_SERVER[PHP_AUTH_USER]' and passwd = md5('$_SERVER[PHP_AUTH_PW]')";
        $result = pg_query($conn, $query);
        if (pg_num_rows($result) == 0 ) {
            authenticate_user();
        } else {
            echo "Welcome";
            session_start();
            while ($row = pg_fetch_array($result))
            {
                $_SESSION['username'] = $row['name'];
                $_SESSION['uid'] = $row['uid']];
                $_SESSION['pw_hash'] = $row['passwd'];
            }
            echo "Your session id is " . session_id();
        }
    }
?>
