<?php
include("trader-functions.php");
redirect_login_pf();
draw_trader_header('chart');
// Load the HTML_QuickForm module
global $db_hostname, $db_database, $db_user, $db_password;

redirect_login_pf();
$username = $_SESSION['username'];
$uid = $_SESSION['uid'];

function create_dropdown($identifier, $pairs, $firstentry, $multiple="", $selected)
{
    // Start the dropdown list with the <select> element and title
    //$dropdown = "<select id=\"$identifier\" name=\"$identifier\" multiple=\"$multiple\">\n";
    $dropdown = "<select id=\"$identifier\" name=\"$identifier\" onchange='this.form.submit()'>>\n";
    #$dropdown .= "<option value=\"\">$firstentry</option>\n";
    // create the dropdown elements
    foreach ($pairs AS $value => $name)
    {
        if ( $value == $selected )
        {
            $dropdown .= "<option selected=\"yes\" value=\"$value\">$name</option>\n";
        }
        else
        {
            $dropdown .= "<option value=\"$value\">$name</option>\n";
        }
    }
    // conclude the dropdown and return it
    $dropdown .= "</select>";
    return $dropdown;
}

// make sure they're logged in"
if (isset($username))
{
    $portfolio = new portfolio($_SESSION['pfid']);
    $pf_id = $portfolio->getID();
    $pf_name = $portfolio->getName();
    $pf_working_date = $portfolio->getWorkingDate();
    $pf_exch = $portfolio->getExch()->getID();
    if (isset($_REQUEST['TickerSymbol']))
    {
        $symb = $_REQUEST['TickerSymbol'];
    }
    if (isset($_SESSION['chart_period']))
    {
        $chart_period = $_SESSION['chart_period'];
    }
    else
    {
        $chart_period = 180;
    }

    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select a.symb, a.name from stocks a, quotes b where a.symb = b.symb and a.exch = b.exch and a.exch = '$pf_exch' and a.exch = b.exch and b.volume > 0 and b.date = '$pf_working_date' order by symb;";
    $first = true;
    foreach ($pdo->query($query) as $row)
    {
        $value = $row['symb'];
        $name = $row['name'];
        $pairs[$value] = "$value.$pf_exch $name";
        if ($first)
        {
            $first = false;
            $first_symb = $value;
        }
    }
    if ( ! isset($symb))
    {
        $symb = $first_symb;
    }
    $symb_name = get_symb_name($symb, $pf_exch);
    print '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="chart" id="chart">';
    print "<h1 align=\"center\">$symb.$pf_exch: $symb_name</h1>";
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print '<tr><td>';
    echo create_dropdown("TickerSymbol", $pairs, "TickerSymbol", "", $symb);
    print "<tr><td><img SRC=\"/cgi-bin/chartstock.php?TickerSymbol=$symb&TimeRange=$chart_period&working_date=$pf_working_date&exch=$pf_exch&ChartSize=S&Volume=1&VGrid=1&HGrid=1&LogScale=0&ChartType=OHLC&Band=None&avgType1=SMA&movAvg1=10&avgType2=SMA&movAvg2=25&Indicator1=RSI&Indicator2=MACD&Indicator3=WilliamR&Indicator4=TRIX&Button1=Update%20Chart\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/close_sma.php?symb=$symb\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/close_ma_diff.php?symb=$symb\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/close_ema.php?symb=$symb\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/exchange_indicators.php\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "</table>\n";
    print "</form>\n";
}
else
{
    tr_warn('No username in cookie');
}

?>
