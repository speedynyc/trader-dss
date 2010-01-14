#!/usr/bin/perl -w

# read all the quotes from a single file and pump them into the quotes table

use strict;
use Date::Manip;
use File::Basename;
use DBI;

$| = 1;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = 'happy';
my (%stock, %start_dates);
my (@files, $file, $line);
my ($stock_code, $date, $exchange, $open, $high, $low, $close, $volume, $adj_close);
my ($isth, @lines, @row, $found_code, $first_quote, $last_quote);
my ($sth, $this_quote_date);

my $initial_upload = 1;
my $debug = 0;

@files = @ARGV;
die "[FATAL]Must give a list of CSV files to load\n" unless (@files + 0);

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

foreach $file (@files)
{
    if ( -f "stop" )
    {
        print "[INFO]Exiting on stop file\n";
        exit;
    }
    if (! -f $file)
    {
        print "[WARN]No such file $file\n";
        next;
    }
    if ( ! open(CSV, "<$file"))
    {
        print "[WARN]Unable to open $file: $!\n";
        next;
    }
    while ($line = <CSV>)
    {
        chomp($line);
        next if ($line =~ m/Date,Open,High,Low,Close,Volume,Adj Close/);
        ($date, $stock_code, $exchange, $open, $high, $low, $close, $volume, $adj_close) = split(/,/, $line);
        $this_quote_date = ParseDate($date);
        # skip this entry if the stock has been seen and the record is in the range of dates seen
        if ($found_code)
        {
            #print "[INFO]X $found_code, $this_quote_date, $first_quote, $last_quote\n";
            next if ( Date_Cmp($this_quote_date,$first_quote) >= 0 and Date_Cmp($this_quote_date, $last_quote) <= 0);
            #next if ( $found_code and ( $this_quote_date >= $first_quote and $this_quote_date <= $last_quote ))
        }

        #print "date:$date\tsymb:$stock_code\texch:$exchange\topen:$open\thigh:$high\tlow:$low\tclose:$close\tvolume:$volume\tadj_close:$adj_close\n";
        print "[DEBUG]insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adj_close)\n" if ($debug);
        print "[DEBUG]'$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adj_close\n";
        $isth = $dbh->prepare("insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adj_close)") or die $dbh->errstr;
        $isth->execute or die $dbh->errstr;
        $isth->finish;
    }
    close(CSV);
}
