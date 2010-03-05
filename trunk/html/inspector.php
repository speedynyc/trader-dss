<?php
include("trader-functions.php");
// Load the HTML_QuickForm module
global $db_hostname, $db_database, $db_user, $db_password;

redirect_login_pf();
$username = $_SESSION['username'];
$uid = $_SESSION['uid'];

// make sure they're logged in"
if (isset($username))
{
    $portfolio = new portfolio($_SESSION['pfid']);
    $pf_id = $portfolio->getID();
    $pf_name = $portfolio->getName();
    $pf_working_date = $portfolio->getWorkingDate();
    $pf_exch = $portfolio->getExch()->getID();
    $symb = $_GET['symb'];
    $symb_name = get_symb_name($symb, $pf_exch);
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
    print "<h1 align=\"center\">$symb.$pf_exch: $symb_name</h1>";
    print '<table border="1" cellpadding="5" cellspacing="0" align="center">';
    print "<tr><td><img SRC=\"/cgi-bin/chartstock.php?TickerSymbol=$symb&TimeRange=$chart_period&working_date=$pf_working_date&exch=$pf_exch&ChartSize=S&Volume=1&VGrid=1&HGrid=1&LogScale=0&ChartType=OHLC&Band=None&avgType1=SMA&movAvg1=10&avgType2=SMA&movAvg2=25&Indicator1=RSI&Indicator2=MACD&Indicator3=WilliamR&Indicator4=TRIX&Button1=Update%20Chart\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/close_sma.php?symb=$symb\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/close_ma_diff.php?symb=$symb\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/close_ema.php?symb=$symb\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "<tr><td><img SRC=\"/cgi-bin/exchange_indicators.php\" ALIGN=\"bottom\" BORDER=\"0\"></td></tr>";
    print "</table>\n";
}
else
{
    tr_warn('No username in cookie');
}

?>
