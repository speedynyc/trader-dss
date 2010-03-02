<?php
require_once("../ChartDirector/lib/FinanceChart.php");
include("../html/trader-functions.php");

redirect_login_pf();

# Loads the date, high, low, open, close, volume from the database
function load_trade_data($symb, $startDate, $endDate)
{
    global $db_hostname, $db_database, $db_user, $db_password, $scramble_names;
    global $timeStamps, $volData, $highData, $lowData, $openData, $closeData, $exch;
    global $first_date, $last_date;
    $first = true;
    $timeStamps = array();
    $highData = array();
    $lowData = array();
    $openData = array();
    $closeData = array();
    $volData = array();
    try {
        $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Cannot connect: " . $e->getMessage());
    }
    $query = "select date, high, low, open, close, volume from quotes where symb = '$symb' and exch = '$exch' and date >= '$startDate' and date <= '$endDate' order by date";
    foreach ($pdo->query($query) as $row)
    {
        $timeStamps[] = chartTime2(strtotime($row['date']));
        if ($first)
        {
            $first_date = $row['date'];
            $first = false;
        }
        $last_date = $row['date'];
        $highData[] = $row['high'];
        $lowData[] = $row['low'];
        $openData[] = $row['open'];
        $closeData[] = $row['close'];
        $volData[] = $row['volume'];
    }
}

# The timeStamps, volume, high, low, open and close data
$timeStamps = null;
$volData = null;
$highData = null;
$lowData = null;
$openData = null;
$closeData = null;

## get the variables provided
#
# Exchange
if (isset($_REQUEST['exch']))
{
    $exch = $_REQUEST['exch'];
}
else
{
    $exch = 'L';
}
# working_date which is the 'today' of the query
if (isset($_REQUEST['working_date']))
{
    $endDate = chartTime2(strtotime($_REQUEST['working_date']));
}
else
{
    $endDate = chartTime2(time());
}

# The no of days to chart
$durationInDays = (int)($_REQUEST["TimeRange"]);
# the symbol to chart
$symb = $_REQUEST["TickerSymbol"];
$stock_name = get_symb_name($symb, $exch);

# Create a FinanceChart object
$c = new FinanceChart(500);

# count back $durationInDays seconds to find the start date
$startDate = $endDate - ($durationInDays*24*60*60);

$first_date = $c->formatValue($startDate, "{value|yyyy-mm-dd}");
$last_date = $c->formatValue($endDate, "{value|yyyy-mm-dd}");


#die("$durationInDays, $startDate:$first_date, $endDate:$last_date");
load_trade_data($symb, $first_date, $last_date);


# Add a title to the chart
$c->addTitle("$symb.$exch $stock_name, from $first_date to $last_date");

# Set the data into the finance chart object
$c->setData($timeStamps, $highData, $lowData, $openData, $closeData, $volData, 0);

# Add the main chart
$MainChart = $c->addMainChart(300);

if (isset($_REQUEST['ref_date']))
{
    $reference_date = chartTime2(strtotime($_REQUEST['ref_date']));
    $found_date = false;
    for($timestampIndex=0; $timestampIndex < count($timeStamps); $timestampIndex++) {
        if($timeStamps[$timestampIndex] == $reference_date)
        {
            $found_date = true;
            break;
        }
    }
    if ($found_date)
    {
        $xMark = $MainChart->xAxis->addMark($timestampIndex, 0x0000ff, "");
        $xMark->setLineWidth(1);
        $xMark->setAlignment(Left);
    }
}
if (isset($_REQUEST['price']))
{
    $price = $_REQUEST['price'];
    $yMark = $MainChart->yAxis->addMark($price, 0xff0000, "$price");
    $yMark->setLineWidth(1);
    $yMark->setAlignment(TopLeft);
}

# Add a simple moving average to the main chart, using brown color
$c->addSimpleMovingAvg(10, 0x663300);

# Add another simple moving average to the main chart, using purple color
$c->addSimpleMovingAvg(30, 0x9900ff);

# Add an HLOC symbols to the main chart, using green/red for up/down days
$c->addHLOC(0x008000, 0xcc0000);

# Add a 75 pixels volume bars sub-chart to the bottom of the main chart, using
# green/red/grey for up/down/flat days
$c->addVolBars(75, 0x99ff99, 0xff9999, 0x808080);
$c->addOBV(75, 0x0000ff);
$c->addRSI(75, 14, 0x800080, 20, 0xff0000, 0x0000ff);
$c->addMomentum(75, 12, 0x0000ff);
$MCAD = $c->addMACD(75, 26, 12, 9, 0x0000ff, 0xff00ff, 0x008000);
#$xMark = $MCAD->xAxis->addMark($timestampIndex, 0xff0000, "");
#$xMark->setLineWidth(1);
#$xMark->setAlignment(Left);

$c->addWilliamR(75, 14, 0x800080, 30, 0xff6666, 0x6666ff);

# Output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
