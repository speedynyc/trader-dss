#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.

use strict;
use DBI;
use strict;
use Proc::Queue size => 4;  # max of 4 child processes. This should match the number of CPUs you want to keep busy
use POSIX ":sys_wait_h"; # imports WNOHANG

$| = 1;
my $debug = 1;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = 'happy';
my $exchange = 'L';
my (@row, $dbh, $sth, $found_code, $last_quote, $last_quote_plus, $isth);
my ($a, $b, $c, $d, $e, $f);
my ($symb, $date, $open, $high, $low, $close, $volume, $adjusted);
my ($q, $stock_code, $row, $first_quote);
my $total_inserts=0;
my $stopfile = 'stop';
my $pausefile = 'pause';
my $execute = 1;
my $update_query;
my $dbhc;
my $pid;

$dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password, {pg_server_prepare => 0}) or die $DBI::errstr;

my $query = "select symb, exch from stocks order by symb, exch";
print "$query\n" if ($debug);
$sth = $dbh->prepare("$query") or die $dbh->errstr;
$sth->execute or die $dbh->errstr;
while ((@row) = $sth->fetchrow_array)
{
    $symb = $row[0];
    $exchange = $row[1];
    $total_inserts++;
    if ($execute)
    {
        $pid = fork();
        if ( defined($pid) and $pid==0 )
        {
            $update_query = "select update_gaps(date, symb, exch, low, high) from quotes where symb = '$symb' and exch = '$exchange' order by date;";
            print "[START]$total_inserts $update_query\n";
            $dbhc = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password, {pg_server_prepare => 0}) or die $DBI::errstr;
            $isth = $dbhc->prepare($update_query) or die $dbh->errstr;
            $isth->execute() or die $dbh->errstr;
            print "[DONE]$total_inserts $update_query\n";
            exit(0);
        }
        1 while waitpid(-1, WNOHANG)>0; # reaps children
        pause_or_stop();
    }
}
print "[INFO]Total rows added $total_inserts\n";

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
