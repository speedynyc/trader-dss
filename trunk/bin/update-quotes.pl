#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.

# $Header: /home/trader/bin/RCS/update-quotes.pl,v 1.2 2010/01/03 10:05:22 trader Exp trader $

use strict;
use Finance::QuoteHist::Yahoo;
use Date::Manip;
use DBI;
use strict;

$| = 1;
my $debug = 0;
my $sleep_time = 3;

Date_Init("DateFormat=non-US");

my $dbname   = 'trader';
my $username = 'postgres';
my $password = '';
my $exchange = 'L';
my (@row, $dbh, $sth, $found_code, $last_quote, $last_quote_plus, $isth);
my ($a, $b, $c, $d, $e, $f);
#my $useragent = LWP::UserAgent->new;
my ($symbol, $date, $open, $high, $low, $close, $volume, $adjusted);
my ($q, $stock_code, $row);
my $total_inserts=0;

my $last_business_day = DateCalc("today","- 1 business day");
$last_business_day = Date_PrevWorkDay($last_business_day, -1);
print "[INFO]last business day is " . UnixDate($last_business_day, "%Y-%m-%d") . "\n" if ($debug);

$dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

print "select symb,exch,first_quote,last_quote from stocks where exch = \"$exchange\" order by symb;\n" if ($debug);
$sth = $dbh->prepare("select symb,exch,first_quote,last_quote from stocks where exch = '$exchange' and first_quote is not null order by symb;") or die $dbh->errstr;
$sth->execute or die $dbh->errstr;
while ((@row) = $sth->fetchrow_array)
{
    $stock_code = $row[0];
    $exchange = $row[1];
    if ( ! $row[3] )
    {
        $last_quote_plus = '2000-01-01';
    }
    $last_quote_plus = DateCalc($row[3], "+ 1 day");
    $last_quote_plus = UnixDate($last_quote_plus, "%Y-%m-%d");
    if (Date_Cmp($last_business_day, $last_quote_plus) <= 0)
    {
	    print "[INFO]Skipping $stock_code up to date\n" if ($debug);
	    next;
    }
    print "[INFO][Seeking update to], @row\n";
    sleep $sleep_time;
    $q = new Finance::QuoteHist::Yahoo(
	    symbols    => [qq($stock_code.$exchange)],
	    start_date => $last_quote_plus,
	    end_date   => 'today',
	    verbose    => 0
    );
    $q->adjusted(0);
    foreach $row ($q->quotes())
    {
	    ($symbol, $date, $open, $high, $low, $close, $volume, $adjusted) = @$row;
	    print "[INFO][inserting]$symbol, $date, $open, $high, $low, $close, $volume, $adjusted\n";
	    print "insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adjusted)\n" if ($debug);
	    $isth = $dbh->prepare("insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adjusted)") or die $dbh->errstr;
	    $isth->execute or die $dbh->errstr;
	    ++$total_inserts;
    }
}
print "[INFO]Total rows added $total_inserts\n";
$isth->finish;
