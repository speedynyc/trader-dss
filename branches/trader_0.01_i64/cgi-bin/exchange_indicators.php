<?php
include("../html/trader-functions.php");
#require_once("../ChartDirector/lib/FinanceChart.php");
require_once("../ChartDirector/lib/phpchartdir.php");
global $db_hostname, $db_database, $db_user, $db_password;

redirect_login_pf();
$username = $_SESSION['username'];
$uid = $_SESSION['uid'];

// make sure they're logged in"
if (isset($username))
{
    $c1 = new XYChart(640, 180);
    $c2 = new XYChart(640, 180);
    $c3 = new XYChart(640, 180);
    $m = new MultiChart(640, 3*180);
    $portfolio = new portfolio($_SESSION['pfid']);
    $pf_id = $portfolio->getID();
    $pf_name = $portfolio->getName();
    $pf_working_date = $portfolio->getWorkingDate();
    $pf_exch = $portfolio->getExch()->getID();
    $pf_exch_name = $portfolio->getExch()->getName();
    $endDate = chartTime2(strtotime($pf_working_date));
    if (isset($_SESSION['chart_period']))
    {
        $chart_period = $_SESSION['chart_period'];
    }
    else
    {
        $chart_period = 180;
    }
    $durationInDays = (int)($chart_period);
    $startDate = $endDate - ($durationInDays*24*60*60);
    $first_date = $c1->formatValue($startDate, "{value|yyyy-mm-dd}");
    $count = 0;
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select date, adv_dec_spread, adv_dec_line, adv_dec_ratio from exchange_indicators where date >= '$first_date' and date <= '$pf_working_date' and exch = '$pf_exch' order by date limit $chart_period;";
    foreach ($pdo->query($query) as $row)
    {
        $a_d_spread[] = $row['adv_dec_spread'];
        $a_d_line[] = $row['adv_dec_line'];
        $a_d_ratio[] = $row['adv_dec_ratio'];
        $dates[] = chartTime2(strtotime($row['date']));
        if ($count == 0)
        {
            // save the first found date for the chart title
            $first_date = $row['date'];
        }
        $count++;
    }
    // Set the plotarea at (50, 30) and of size 240 x 140 pixels. Use white (0xffffff) 
    // background. 
    $plotAreaObj1 = $c1->setPlotArea(50, 45, 410, 100, 0xffffff, 0xffffff, 0xc0c0c0, 0xc0c0c0, 0xc0c0c0);
    $plotAreaObj2 = $c2->setPlotArea(50, 45, 410, 100, 0xffffff, 0xffffff, 0xc0c0c0, 0xc0c0c0, 0xc0c0c0);
    $plotAreaObj3 = $c3->setPlotArea(50, 45, 410, 100, 0xffffff, 0xffffff, 0xc0c0c0, 0xc0c0c0, 0xc0c0c0);
    // Add a legend box at (50, 185) (below of plot area) using horizontal layout. Use 8 
    // pts Arial font with Transparent background. 
    $legendObj1 = $c1->addLegend(50, 45, false, "", 8);
    $legendObj1->setBackground(Transparent);
    $legendObj2 = $c2->addLegend(50, 45, false, "", 8);
    $legendObj2->setBackground(Transparent);
    $legendObj3 = $c3->addLegend(50, 45, false, "", 8);
    $legendObj3->setBackground(Transparent);
    // Add a title box to the chart using 8 pts Arial Bold font, with yellow (0xffff40)
    // background and a black border (0x0)
    $m->addTitle("$pf_exch_name Breadth indicators from $first_date to $pf_working_date", "arialbd.ttf", 12);
    // Set the y axis label format to US$nnnn 
    $m_yearFormat = "{value|yyyy}";
    $m_firstMonthFormat = "<*font=bold*>{value|mmm yy}";
    $m_otherMonthFormat = "{value|mmm}";
    $m_firstDayFormat = "<*font=bold*>{value|d mmm\nyyyy}";
    $m_otherDayFormat = "{value|d}";
    $m_firstHourFormat = "<*font=bold*>{value|d mmm\nh:nna}";
    $m_otherHourFormat = "{value|h:nna}";
    if ($count < 370)
    {
        $format = $m_firstDayFormat;
    }
    else
    {
        $format = $m_firstMonthFormat;
    }
    $c1->xAxis->setMultiFormat(StartOfDayFilter(), $format, StartOfDayFilter(1, 0.5), $m_otherDayFormat, 1);
    $mark1 = $c1->yAxis->addMark(1, -1, "");
    $c2->xAxis->setMultiFormat(StartOfDayFilter(), $format, StartOfDayFilter(1, 0.5), $m_otherDayFormat, 1);
    $c3->xAxis->setMultiFormat(StartOfDayFilter(), $format, StartOfDayFilter(1, 0.5), $m_otherDayFormat, 1);
    $mark3 = $c3->yAxis->addMark(0, -1, "");
    // add the colouring to the area between 1 and the current plot line
    $lineLayerObj1 = $c1->addLineLayer($a_d_ratio, $c1->yZoneColor(1, 0xff3333, 0x008800), 'A/D Ratio');
    $c1->addInterLineLayer($lineLayerObj1->getLine(), $mark1->getLine(), $c1->linearGradientColor(0, 50, 0, 255, 0xffffff, 0x008800), $c1->linearGradientColor(0, 50, 0, 255, 0xffffff, 0xff0000));
    $lineLayerObj1->setXData($dates);
    $lineLayerObj2 = $c2->addLineLayer($a_d_line, -1, 'A/D Line');
    $lineLayerObj2->setXData($dates);
    $lineLayerObj3 = $c3->addLineLayer($a_d_spread, $c3->yZoneColor(0, 0xff3333, 0x008800), 'A/D Spread');
    $c3->addInterLineLayer($lineLayerObj3->getLine(), $mark3->getLine(), $c3->linearGradientColor(0, 50, 0, 255, 0xffffff, 0x008800), $c3->linearGradientColor(0, 50, 0, 255, 0xffffff, 0xff0000));
    $lineLayerObj3->setXData($dates);
    // Output the chart 
    header("Content-type: image/png");
    $m->addChart(0, 0, $c1);
    $m->addChart(0, 150, $c2);
    $m->addChart(0, 300, $c3);
    print($m->makeChart2(PNG));
}
else
{
    return;
}
?> 
