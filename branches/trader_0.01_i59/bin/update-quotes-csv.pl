#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.

use strict;
use DBI;

$| = 1;
my $debug = 0;
my $sleep_time = 15;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = 'happy';
my (@row, $dbh, $sth, $found_code, $last_quote, $last_quote_plus, $isth);
my ($a, $b, $c, $d, $e, $f);
my ($symbol, $date, $open, $high, $low, $close, $volume, $adjusted);
my ($tmp, $q, $stock_code, $row, $first_quote, $query, $line, $symb, $exch);
my $total_inserts=0;
my $stopfile = 'stop';
my $pausefile = 'pause';
my $quotes = '/tmp/quotes.txt';

$dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

open(QUOTES, "<$quotes") or die "[FATAL]Unable to open $quotes: $!\n";
while ($line = <QUOTES>)
{
    chomp($line);
    ($date, $symb, $exch, $open, $high, $low, $close, $volume, $adjusted) = split(/,/, $line);
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
    print "[INFO][inserting $total_inserts]$symb, $date, $open, $high, $low, $close, $volume, $adjusted\n";
    $query = "insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$symb', '$exch', $open, $high, $low, $close, $volume, $adjusted);";
    print "$query\n" if ($debug);
    $isth = $dbh->prepare($query) or die $dbh->errstr;
    $isth->execute or die $dbh->errstr;
    ++$total_inserts;
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
