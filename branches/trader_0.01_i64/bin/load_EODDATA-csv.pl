#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.
# expected format like this
#  Ass British Food,ABF.L,19900102,424.25,427.3,424.25,424.25,42725
#  Barclays Bank,BARC.L,19900102,101.43,102.68,101.43,102.32,920456
#  British American Tobacco Plc,BATS.L,19900102,246.62,249.84,246.04,246.62,125620

use strict;
use DBI;
use Proc::Queue size => 4;    # max of 4 child processes. This should match the number of CPUs you want to keep busy
use POSIX ":sys_wait_h";      # imports WNOHANG

$| = 1;
my $debug = 0;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = 'happy';
my (@row, $dbh, $sth, $found_code, $last_quote, $last_quote_plus, $isth);
my ($a, $b, $c, $d, $e, $f);
my ($symbol, $date, $open, $high, $low, $close, $volume, $adjusted, $name);
my ($tmp, $q, $stock_code, $row, $first_quote, $query, $line, $symb, $exch, $first);
my ($symb_query, $symb_sth, @symb_results, $stock_name, $quotes_file, $pid);
my $file_count    = 0;
my $stopfile      = 'stop';
my $pausefile     = 'pause';
#my $quotes        = '/home/pstubbs/trader/data/EODDATA-LSE.txt';
my $quotes_dir = '/home/pstubbs/trader/data/breakout';
$exch = 'L';    # just dealing with LSE atm.

foreach $quotes_file (<$quotes_dir/*.txt>)
{
    $file_count++;
    print "[INFO]$file_count: $quotes_file\n";
    # fork here
    $pid = fork();
    if (defined($pid) and $pid == 0)
    {
        $dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;
        open(QUOTES, "<$quotes_file") or die "[FATAL]Unable to open $quotes_file: $!\n";
        $first = 1;
        while ($line = <QUOTES>)
        {
            chomp($line);
            next if ($line =~ m/^#/);
            ($name, $symb, $date, $open, $high, $low, $close, $volume) = split(/,/, $line);
            $name =~ s/'/''/g;        # quote quotes
            $symb =~ s/\.$exch$//;    # rip off the exchange from the end of the symbol, NOTE: BT is BT.A.L, so this leaves it as BT.A
            $adjusted = $close;
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
            if ($first)
            {
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
                        $symb_sth = $dbh->prepare($symb_query) or die $dbh->errstr;
                        $symb_sth->execute or die $dbh->errstr;
                    }
                }
                $first = 0;
            }
            $query = "insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$symb', '$exch', $open, $high, $low, $close, $volume, $adjusted);";
            if ($debug)
            {
                print "[INFO][inserting]$symb, $date, $open, $high, $low, $close, $volume, $adjusted\n";
                print "[DEBUG]$query\n";
            }
            else
            {
                $isth = $dbh->prepare($query) or die $dbh->errstr;
                $isth->execute or die $dbh->errstr;
                $isth->finish;
            }
        }
        exit 0; # exit the subprocess
    }
    pause_or_stop();
}
$query = "select update_all_exchange_indicators('L');";
print "$query\n" if ($debug);
#$sth = $dbh->prepare("$query") or die $dbh->errstr;
#$sth->execute or die $dbh->errstr;
#$sth->finish;

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
