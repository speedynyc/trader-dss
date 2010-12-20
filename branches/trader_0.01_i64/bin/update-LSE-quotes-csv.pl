#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.
# expected format like this
#  Ass British Food,ABF.L,19900102,424.25,427.3,424.25,424.25,42725
#  Barclays Bank,BARC.L,19900102,101.43,102.68,101.43,102.32,920456
#  British American Tobacco Plc,BATS.L,19900102,246.62,249.84,246.04,246.62,125620

use strict;
use DBI;

$| = 1;
my $debug = 0;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = 'happy';
my (@row, $dbh, $sth, $found_code, $last_quote, $last_quote_plus, $isth);
my ($a, $b, $c, $d, $e, $f);
my ($symbol, $date, $open,       $high, $low,         $close, $volume, $adjusted, $name);
my ($tmp,    $q,    $stock_code, $row,  $first_quote, $query, $line,   $symb,     $exch);
my ($symb_query, $symb_sth, @symb_results, $stock_name);
my $total_inserts = 0;
my $stopfile      = 'stop';
my $pausefile     = 'pause';
my $quotes        = '/home/pstubbs/trader/data/EODDATA-LSE.txt';
$exch = 'L';    # just dealing with LSE atm.

$dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

open(QUOTES, "<$quotes") or die "[FATAL]Unable to open $quotes: $!\n";
while ($line = <QUOTES>)
{
    chomp($line);
    next if ($line =~ m/^#/);
    #($date, $symb, $exch, $open, $high, $low, $close, $volume, $adjusted) = split(/,/, $line);
    ($name, $symb, $date, $open, $high, $low, $close, $volume) = split(/,/, $line);
    $name =~ s/'/''/g;    # quote quotes
    #($symb, $exch) = split(/\./, $symb);
    $symb =~ s/\.$exch$//;    # rip off the exchange from the end of the symbol, NOTE: BT is BT.A.L, so this leaves it as BT.A
    $adjusted = $close;
    #next unless ($volume > 0);    # skip on zero or missing volume
    if ($low == 0 or $high == 0 or $open == 0 or $close == 0)
    {
        # can't have zero prices
        print "[WARN]$symb,$date A price is zero, low = $low, high = $high, open = $open, close = $close\n";
        next;
    }
    if ($low > $high)
    {
        # if low is > high, swap them. Looking at historical data, that looks about right
        print "[WARN]$symb,$date low > high, low = $low, high = $high, open = $open, close = $close\n";
        $tmp  = $low;
        $low  = $high;
        $high = $tmp;
    }
    # fix high to be the max for the day
    if ($open > $high)
    {
        print "[WARN]$symb,$date open > high: low = $low, high = $high, open = $open, close = $close\n";
        $high = $open;
    }
    if ($open < $low)
    {
        print "[WARN]$symb,$date open < low: low = $low, high = $high, open = $open, close = $close\n";
        $low = $open;
    }
    # fix low to be the min for the day
    if ($close < $low)
    {
        print "[WARN]$symb,$date close < low: low = $low, high = $high, open = $open, close = $close\n";
        $low = $close;
    }
    if ($close > $high)
    {
        print "[WARN]$symb,$date close > high: low = $low, high = $high, open = $open, close = $close\n";
        $high = $close;
    }
    # now check the lot and yell if it's wrong
    unless (($low <= $open) and ($low <= $close) and ($low <= $high) and ($high >= $open) and ($high >= $close))
    {
        die "[FATAL]Price crazyness! low = $low, open = $open, high = $high, close = $close\n";
    }
    # check that the stock exists, should check the name matches too
    $symb_query = "select name from stocks where symb = '$symb' and exch = '$exch';";
    $symb_sth = $dbh->prepare($symb_query) or die $dbh->errstr;
    $symb_sth->execute or die $dbh->errstr;
    if ($symb_sth->rows > 0)
    {
        @symb_results = $symb_sth->fetchrow_array;
        ($stock_name) = @symb_results;
        $stock_name =~ s/'/''/g;    # quote quotes
        if ($stock_name ne $name)
        {
            die "[FATAL]Symbol name changed. Was $stock_name, now $name\n";
        }
    }
    else
    {
        $symb_query = "insert into stocks (symb, name, exch, first_quote, last_quote) values ('$symb', '$name', '$exch', '$date', '$date');";
        if ($debug)
        {
            print "[DEBUG]$symb_query\n";
        }
        else
        {
            print "[DEBUG]$symb_query\n";
            $symb_sth = $dbh->prepare($symb_query) or die $dbh->errstr;
            $symb_sth->execute or die $dbh->errstr;
        }
    }
    $query = "insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$symb', '$exch', $open, $high, $low, $close, $volume, $adjusted);";
    print "[INFO][inserting $total_inserts]$symb, $date, $open, $high, $low, $close, $volume, $adjusted\n";
    if ($debug)
    {
        print "[DEBUG]$query\n";
    }
    else
    {
        $isth = $dbh->prepare($query) or die $dbh->errstr;
        $isth->execute or die $dbh->errstr;
        ++$total_inserts;
    }
    pause_or_stop();
}
print "[INFO]Total rows added $total_inserts\n";
print "[INFO]Updating exchange indicators\n";
$query = "select update_all_exchange_indicators('L');";
print "$query\n" if ($debug);
$sth = $dbh->prepare("$query") or die $dbh->errstr;
$sth->execute or die $dbh->errstr;
$sth->finish;
$isth->finish;

sub pause_or_stop
{
    # stop of the stopfile's been created in CWD
    if (-f $stopfile)
    {
        warn "[INFO]Exiting on stopfile\n";
        unlink($stopfile);
        exit 0;
    }
    wait_on_pause();
}

sub wait_on_pause
{
    # sleep if the pausefile's in CWD
    my $pause_time = 60;
    while (-f $pausefile)
    {
        warn "[INFO]Pausing for $pause_time sec.\n";
        sleep $pause_time;
    }
}
