#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.

use strict;
use Finance::QuoteHist;
use Date::Manip;
use DBI;
use strict;

$| = 1;
my $debug = 0;
my $sleep_time = 15;

Date_Init("DateFormat=non-US");

my $dbname   = 'trader';
my $username = 'postgres';
my $password = 'happy';
my $exchange = 'L';
my (@row, $dbh, $sth, $found_code, $last_quote, $last_quote_plus, $isth);
my ($a, $b, $c, $d, $e, $f);
my ($symbol, $date, $open, $high, $low, $close, $volume, $adjusted);
my ($tmp, $q, $stock_code, $row, $first_quote);
my $total_inserts=0;
my $stopfile = 'stop';
my $pausefile = 'pause';

my $last_business_day = DateCalc("today","- 1 business day");
my $six_months_ago = DateCalc("today","- 6 months");
$last_business_day = Date_PrevWorkDay($last_business_day, -1);
print "[INFO]last business day is " . UnixDate($last_business_day, "%Y-%m-%d") . "\n" if ($debug);
print "[INFO]six months ago is " . UnixDate($six_months_ago, "%Y-%m-%d") . "\n" if ($debug);
$last_business_day = UnixDate($last_business_day, "%Y-%m-%d");
$six_months_ago = UnixDate($six_months_ago, "%Y-%m-%d");

$dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

my $query = "select symb,exch,first_quote,last_quote from stocks where exch = '$exchange' and ((last_quote > '$six_months_ago' and last_quote < '$last_business_day') or last_quote is null) order by symb;\n";
print "$query\n" if ($debug);
$sth = $dbh->prepare("$query") or die $dbh->errstr;
$sth->execute or die $dbh->errstr;
while ((@row) = $sth->fetchrow_array)
{
    $stock_code = $row[0];
    $exchange = $row[1];
    $first_quote = $row[2];
    $last_quote = $row[3];
    if ( ! $last_quote )
    {
        $last_quote = '2000-01-01';
        $last_quote_plus = '2000-01-01';
    }
    else
    {
        $last_quote_plus = DateCalc($row[3], "+ 1 day");
    }
    $last_quote_plus = UnixDate($last_quote_plus, "%Y-%m-%d");
    $first_quote = $last_quote_plus unless ($first_quote);
    sleep $sleep_time;
    print "[INFO][Updating]$stock_code.$exchange, have $first_quote to $last_quote. Retrieving $last_quote_plus to today\n";
    $q = new Finance::QuoteHist(
            lineup     => [qw(
                Finance::QuoteHist::Yahoo
                Finance::QuoteHist::Google
                Finance::QuoteHist::MSN
                Finance::QuoteHist::QuoteMedia
                )],
        symbols    => [qq($stock_code.$exchange)],
        start_date => $last_quote_plus,
        end_date   => 'today',
        verbose    => 0
    );
    #print "[INFO]$stock_code.$exchange from " . $q->quote_source($stock_code, "quote") . "\n";
    $q->adjusted(1);
    foreach $row ($q->quotes())
    {
        ($symbol, $date, $open, $high, $low, $close, $volume, $adjusted) = @$row;
        next unless ($volume); # skip on zero or missing volume
        if ($low > $high)
        {
            # if low is > high, swap them. Looking at historical data, that looks about right
            $tmp = $low;
            $low = $high;
            $high = $tmp;
        }
        if ( $low == 0 or $high == 0 or $open == 0 or $close ==0 )
        {
            # can't have zero prices
            next;
        }
        ($symbol, undef) = split(/\./, $symbol);
        $adjusted = $close if (not defined($adjusted));
        print "[INFO][inserting $total_inserts]$symbol, $date, $open, $high, $low, $close, $volume, $adjusted\n";
        $query = "insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adjusted);";
        print "$query\n" if ($debug);
        $isth = $dbh->prepare($query) or die $dbh->errstr;
        $isth->execute or die $dbh->errstr;
        ++$total_inserts;
    }
    pause_or_stop();
}
print "[INFO]Total rows added $total_inserts\n";
print "[INFO]Updating exchange indicators\n";
$query = "select update_all_exchange_indicators();;\n";
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
