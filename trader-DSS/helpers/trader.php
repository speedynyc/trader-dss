<?php

function t_for_true($value)
{
    // returns true if $value is 't' and false for every other value
    if (isset($value))
    {
        if ($value == 't')
        {
            return true;
        }
    }
    return false;
}

abstract class trader_base
{
    // a base class to stop auto-vivication of object variables
    // and to setup the DB connection
    protected $db_hostname = 'localhost';
    protected $db_database = 'trader';
    protected $db_user     = 'postgres';
    protected $db_password = 'happy';
    protected $dbh;

    public function __construct()
    {
        // setup the DB connection for use in this script
        global $db_hostname, $db_database, $db_user, $db_password;
        try {
            $pdo = new PDO("pgsql:host=$db_hostname;dbname=$db_database", $db_user, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("ERROR: Cannot connect: " . $e->getMessage());
        }
        $this->dbh = $pdo;
    }
    protected function __set($name, $value)
    {
        tr_warn("No such property trader_base __set(): $name = $value");
        die("Object error");
    }
    protected function __get($name)
    {
        tr_warn("No such property trader_base __get(): $name");
        die("Object error");
    }
    protected function get($name)
    {
        if (isset($this->$name))
        {
            return $this->$name;
        }
        else
        {
            die("[FATAL]: No such property  trader_base get() portfolio->$name\n");
        }
    }
}

class exchange extends trader_base
{
    protected $exch, $name, $symb, $currency;
    public function __construct($exch_id)
    {
        parent::__construct();
        $query = "select * from exchange where exch = '$exch_id';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('exchange:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: exchange, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['exch']) and $row['exch'] == $exch_id)
        {
            $this->exch = $row['exch'];
            $this->name = $row['name'];
            $this->symb = $row['curr_desc'];
            $this->currency = $row['curr_char'];
        }
        else
        {
            die("[FATAL]exchange $exch_id missing from exchange table: $query\n");
        }
    }
    public function getID() { return $this->exch; }
    public function getName() { return $this->name; }
    public function getSymb() { return $this->symb; }
    public function getCurrency() { return $this->currency; }
    public function nextTradeDay($date)
    {
        // returns the next trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where date > '$date' and exch = '$exch' order by date asc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('nextTradeDay:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
    public function nearestTradeDay($date)
    {
        // returns the nearest trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where date >= '$date' and exch = '$exch' order by date asc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('nearestTradeDay:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
    public function firstDate()
    {
        // returns the first trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where exch = '$exch' order by date asc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('firstDate:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
    public function lastDate()
    {
        // returns the first trading day for the exchange
        $exch = $this->exch;
        $query = "select date from trade_dates where exch = '$exch' order by date desc limit 1;";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('lastDate:' . $query . ':' . $e->getMessage());
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $next_date = $row['date'];
        return $next_date;
    }
}

class portfolio extends trader_base
{
    protected $pfid, $name, $exch, $parcel, $working_date, $hide_names, $stop_loss, $auto_stop_loss;
    protected $cashInHand, $holdings, $openingBalance, $startDate, $countOfDaysTraded;
    protected $commission, $tax_rate;
    public function __construct($pfid)
    {
        // setup the the parent class (db connection etc)
        parent::__construct();
        // set all the 'lazy evaluate values to impossible numbers
        $this->countOfDaysTraded = -1;
        $query = "select * from portfolios where pfid = '$pfid';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('portfolio:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['pfid']) and $row['pfid'] == $pfid)
        {
            $this->pfid = $row['pfid'];
            $this->name = $row['name'];
            $this->exch = new exchange($row['exch']);
            $this->openingBalance = $row['opening_balance'];
            $this->parcel = $row['parcel'];
            $this->working_date = $row['working_date'];
            $this->hide_names = t_for_true($row['hide_names']);
            $this->stop_loss = $row['stop_loss'];
            $this->auto_stop_loss = t_for_true($row['auto_stop_loss']);
            $this->commission = $row['commission'];
            $this->tax_rate = $row['tax_rate'];
        }
        else
        {
            die("[FATAL]portfolio $pfid missing from portfolios table: $query\n");
        }
        // get the start date by finding the first record for the portfolio in pf_summary
        $query = "select * from pf_summary where pfid = '$pfid' order by date asc limit 1";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('portfolio:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['pfid']) and $row['pfid'] == $pfid)
        {
            $this->startDate = $row['date'];
        }
        // get current balance and holdings by finding the most recent entry in pf_summary
        $query = "select * from pf_summary where pfid = '$pfid' order by date desc limit 1";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('portfolio:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['pfid']) and $row['pfid'] == $pfid)
        {
            $this->cashInHand = $row['cash_in_hand'];
            $this->holdings = $row['holdings'];
        }
        else
        {
            tr_warn('portfolio:__construct: $row[\'pfid\'] not defined: ' . $query);
            die("[FATAL]Class: portfolio, function: __construct\n");
        }
    }
    public function getID() { return $this->pfid; }
    public function getExch() { return $this->exch; }
    public function getName() { return $this->name; }
    public function getStopLoss() { return $this->stop_loss; }
    public function getAutoStopLoss() { return $this->auto_stop_loss; }
    public function symbNamesHidden() { return $this->hide_names; }
    public function getWorkingDate() { return $this->working_date; }
    public function getParcel() { return $this->parcel; }
    public function getStartDate() { return $this->startDate; }
    public function getCashInHand() { return $this->cashInHand; }
    public function getHoldings() { return $this->holdings; }
    public function getCommission() { return $this->commission; }
    public function getTaxRate() { return $this->tax_rate; }
    public function getOpeningBalance() { return $this->openingBalance; }
    public function countDaysTraded()
    {
        // returns the next trading day for the exchange
        $pfid = $this->pfid;
        if ($this->countOfDaysTraded == -1)
        {
            $query = "select count(*) as days from pf_summary where pfid = '$pfid';";
            try 
            {
                $result = $this->dbh->query($query);
            }
            catch (PDOException $e)
            {
                tr_warn('countDaysTraded:' . $query . ':' . $e->getMessage());
            }
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $this->countOfDaysTraded = $row['days'];
            return $this->countOfDaysTraded;
        }
        else
        {
            return $this->countOfDaysTraded;
        }
    }
    public function dayGain($days=1)
    {
        $current_total = $this->cashInHand + $this->holdings;
        $pfid = $this->pfid;
        $working_date = $this->working_date;
        /*
           set compare_total to working_total so that if we only have the first record (a new portfolio for exahple)
           It doesn't appear as a gain of the opening balance.
         */
        $previous_total = $current_total;
        // simple hack, we select $days days before today and the last one we reach is the one we want
        $query = "select pfid, date, (cash_in_hand + holdings) as total from pf_summary where pfid = '$pfid' and date < '$working_date' order by date desc limit '$days'";
        foreach ($this->dbh->query($query) as $row)
        {
            $previous_total = $row['total'];
        }
        return $current_total - $previous_total;
    }
}

class security extends trader_base
{
    protected $symb, $name, $exch, $pfid;
    protected $firstQuote, $lastQuote;
    public function __construct($symb, $exch)
    {
        // setup the the parent class (db connection etc)
        parent::__construct();
        $this->exch = new exchange($exch);
        if (isset($_SESSION['pfid']))
        {
            $this->pfid = $_SESSION['pfid'];
        }
        else
        {
            $this->pfid = -1;
        }
        // load the info from the stocks table
        $query = "select * from stocks where symb = '$symb' and exch = '$exch';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('security:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: security, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['symb']) and $row['symb'] == $symb)
        {
            $this->name = $row['name'];
            $this->firstQuote = $row['first_quote'];
            $this->lastQuote = $row['last_quote'];
        }
    }
    protected function isInTable($table)
    {
        // check if the symbol is held in the current portfolio
        if ($this->pfid > 0)
        {
            $symb = $this->symb;
            $pfid = $this->pfid;
            $query = "select count(*) as count from $table where symb = '$symb' and pfid = '$pfid';";
            try 
            {
                $result = $this->dbh->query($query);
            }
            catch (PDOException $e)
            {
                tr_warn('security:isInTable:' . $query . ':' . $e->getMessage());
                die("[FATAL]Class: security, function: isInTable\n");
            }
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if (isset($row['count']) and $row['count'] > 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    public function isInHolding() { return isInTable('holdings'); }
    public function isInCart() { return isInTable('cart'); }
    public function isInWatch() { return isInTable('watch'); }
}

class quote extends security
{
    protected $open, $close, $high, $low, $volume;
    protected $dates, $highData, $lowData, $openData, $closeData, $volData;
    protected $max, $min, $maxDate, $minDate;
    protected $loadedStartDate, $loadedEndDate;
    public function __construct($symb, $exch, $date)
    {
        // setup the the parent class (db connection etc)
        parent::__construct($symb, $exch);
        // load the info from the stocks table
        $query = "select * from quotes where symb = '$symb' and exch = '$exch' and date = '$date';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('quote:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: quote, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['symb']) and $row['symb'] == $symb)
        {
            $this->open = $row['open'];
            $this->high = $row['high'];
            $this->low = $row['low'];
            $this->close = $row['close'];
            $this->volume = $row['volume'];
        }
    }
    public function getOpen() { return $this->open; }
    public function getHigh() { return $this->high; }
    public function getLow() { return $this->low; }
    public function getClose() { return $this->close; }
    public function getVolume() { return $this->volume; }
    public function getPrice($qty)
    {
        return $this->close * $qty;
    }
    protected function loadQuotes($startDate, $endDate)
    {
        // load all the quotes for the period
        $this->loadedStartDate = $startDate;
        $this->loadedEndDate = $endDate;
        // zero the existing values
        unset($this->dates, $this->highData, $this->lowData);
        unset($this->openData, $this->closeData, $this->volData);
        unset($this->max, $this->min);
        $exch = $this->exch->getID();
        $symb = $this->symb;
        $query = "select date, high, low, open, close, volume from quotes where symb = '$symb' and exch = '$exch' and date >= '$startDate' and date <= '$endDate' order by date";
        foreach ($this->dbh->query($query) as $row)
        {
            $this->dates[] = $row['date'];
            $this->highData[] = $row['high'];
            $this->lowData[] = $row['low'];
            $this->openData[] = $row['open'];
            $this->closeData[] = $row['close'];
            $this->volData[] = $row['volume'];
            if ($row['high'] > $this->max)
            {
                $this->max = $row['high'];
                $this->maxDate = $row['date'];
            }
            if ($row['low'] < $this->min)
            {
                $this->min = $row['min'];
                $this->minDate = $row['date'];
            }
        }
    }
    public function getMin($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->min;
    }
    public function getMinDate($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->minDate;
    }
    public function getMax($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->max;
    }
    public function getMaxDate($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->maxDate;
    }
    public function getHighs($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->highData;
    }
    public function getLows($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->lowData;
    }
    public function getOpens($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->openData;
    }
    public function getCloses($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->closeData;
    }
    public function getVolumes($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->volData;
    }
    public function getDates($startDate, $endDate)
    {
        if ($startDate != $this->loadedStartDate or $endDate != $this->loadedEndDate)
        {
            // load the data if not already loaded
            $this->loadQuotes($startDate, $endDate);
        }
        return $this->dates;
    }
}

class holding extends quote
{
    protected $hid, $buyPrice, $workingDate, $pfid, $openDate, $qty, $comment;
    public function __construct($symb, $portfolio)
    {
        // setup the the parent class (db connection etc)
        $workingDate = $portfolio->getWorkingDate();
        $exch = $portfolio->exch->getID();
        $pfid = $portfolio->getID();
        $this->pfid = $pfid;
        parent::__construct($symb, $exch, $workingDate);
        // load the info from the stocks table
        $query = "select * from holdings where symb = '$symb' and pfid = '$pfid';";
        try 
        {
            $result = $this->dbh->query($query);
        }
        catch (PDOException $e)
        {
            tr_warn('holding:__construct:' . $query . ':' . $e->getMessage());
            die("[FATAL]Class: holding, function: __construct\n");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (isset($row['symb']) and $row['symb'] == $symb)
        {
            $this->hid = $row['hid'];
            $this->pfid = $row['pfid'];
            $this->openDate = $row['date'];
            $this->price = $row['price'];
            $this->qty = $row['volume'];
            $this->comment = $row['comment'];
        }
    }
    public function getHid() { return $this->hid; }
    public function getPfid() { return $this->pfid; }
    public function getOpenDate() { return $this->openDate; }
    public function getPrice() { return $this->price; }
    public function getQty() { return $this->qty; }
    public function getComment() { return $this->comment; }
    public function getGain()
    {
        return ($this->price * abs($this->qty)) + ($this->qty * ($this->cose - $this->price));
    }
    public function getValue()
    {
        return ($this->price * abs($this->qty)) + ($this->qty * ($this->cose - $this->price));
    }
    public function getCost()
    {
        return ($this->price * abs($this->qty));
    }
    public function IsGain()
    {
        $gain = $this->getGain();
        if ( $gain < 0 )
        {
            return -1;
        }
        elseif ($gain == 0)
        {
            return 0;
        }
        else
        {
            return 1;
        }
    }
}

?>
