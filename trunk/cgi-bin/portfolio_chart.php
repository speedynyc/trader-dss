<?php
include("../html/trader-functions.php");
require_once("../ChartDirector/lib/phpchartdir.php");
global $db_hostname, $db_database, $db_user, $db_password;
try {
    $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Cannot connect: " . $e->getMessage());
}

if (isset($_REQUEST['pfid']))
{
    $portfolio = new portfolio($_REQUEST['pfid']);
    $pfid = $portfolio->getID();
    $name = $portfolio->getName();
    #$query = "select date, cash_in_hand, holdings from (select date, cash_in_hand, holdings from pf_summary where pfid = '$pfid' order by date desc limit 50) as base order by date;";
    $query = "select date, cash_in_hand, holdings from pf_summary where pfid = '$pfid' order by date ;;";
    $first = true;
    foreach ($pdo->query($query) as $row)
    {
        if ($first)
        {
            $first_date = $row['date'];
            $first = false;
        }
        $last_date = $row['date'];
        $holdings[] = $row['holdings'];
        $cash_in_hand[] = $row['cash_in_hand'];
        $dates[] = chartTime2(strtotime($row['date']));
    }
    // Create a XYChart object of size 300 x 210 pixels. Set the background to pale yellow 
    // (0xffffc0) with a black border (0x0)
    $c = new XYChart(640, 480);
    // Set the plotarea at (50, 30) and of size 240 x 140 pixels. Use white (0xffffff) 
    // background. 
    $plotAreaObj = $c->setPlotArea(50, 30, 560, 400);
    $plotAreaObj->setBackground(0xffffff);
    // Add a legend box at (50, 185) (below of plot area) using horizontal layout. Use 8 
    // pts Arial font with Transparent background. 
    $legendObj = $c->addLegend(250, 445, false, "", 8);
    $legendObj->setBackground(Transparent);
    // Add a title box to the chart using 8 pts Arial Bold font, with yellow (0xffff40)
    // background and a black border (0x0)
    $textBoxObj = $c->addTitle("$name ($pfid) Performance ($first_date to $last_date)", "arialbd.ttf", 8);
    $textBoxObj->setBackground(0xffff40, 0);
    // Set the y axis label format to US$nnnn 
    $c->yAxis->setLabelFormat("£{value}");
    // Set the labels on the x axis. 
    // Display 1 out of 2 labels on the x-axis. Show minor ticks for remaining labels. 
    $m_yearFormat = "{value|yyyy}";
    $m_firstMonthFormat = "<*font=bold*>{value|mmm yy}";
    $m_otherMonthFormat = "{value|mmm}";
    $m_firstDayFormat = "<*font=bold*>{value|d mmm}";
    $m_otherDayFormat = "{value|d}";
    $m_firstHourFormat = "<*font=bold*>{value|d mmm\nh:nna}";
    $m_otherHourFormat = "{value|h:nna}";
    $m_timeLabelSpacing = 50;
    $c->xAxis->setMultiFormat(StartOfDayFilter(), $m_firstDayFormat, StartOfDayFilter(1, 0.5), $m_otherDayFormat, 1);
    // Add an stack area layer with three data sets 
    $layer = $c->addAreaLayer2(Stack);
    $layer->addDataSet($holdings, 0x4040ff, "Holdings");
    $layer->addDataSet($cash_in_hand, 0xff4040, "Cash");
    $layer->setXData($dates);
    // Output the chart 
    header("Content-type: image/png");
    print($c->makeChart2(PNG));
}
else
{
    return;
}
?> 
