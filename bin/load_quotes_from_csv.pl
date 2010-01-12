#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.

# $Header: /var/lib/pgsql/bin/RCS/load_quotes_from_csv.pl,v 1.4 2007/08/10 14:33:14 postgres Exp postgres $

use strict;
use Date::Manip;
use File::Basename;
use DBI;

$| = 1;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = '';
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
    ($stock_code, $exchange, undef) = split(/\./, basename($file));
    print "select first_quote, last_quote from stock_dates where symb = '$stock_code' and exch = '$exchange'\n" if ($debug);
    $sth = $dbh->prepare("select first_quote, last_quote from stock_dates where symb = '$stock_code' and exch = '$exchange'") or die $dbh->errstr;
    $sth->execute or die $dbh->errstr;
    $found_code = 0;
    while ((@row) = $sth->fetchrow_array)
    {
	print "[INFO]ROW, @row\n" if ($debug);
	$found_code = 1;
	$first_quote = ParseDate($row[0]);
	$last_quote = ParseDate($row[1]);
    }
    $sth->finish;
    if ($found_code and $initial_upload)
    {
	#we're doing an initial upload, don't bother to check if all the lines in the CVS have been uploaded
	print "[INFO]Initial upload, skipping $stock_code\n";
	next;
    }
    if ( ! open(CSV, "<$file"))
    {
	print "[WARN]Unable to open $file: $!\n";
	next;
    }
    @lines = reverse <CSV>;
    close(CSV);
    foreach $line (@lines)
    {
	chomp($line);
	next if ($line =~ m/Date,Open,High,Low,Close,Volume,Adj Close/);
	($date, $open, $high, $low, $close, $volume, $adj_close) = split(/,/, $line);
	$this_quote_date = ParseDate($date);
	# skip this entry if the stock has been seen and the record is in the range of dates seen
	if ($found_code)
	{
	    #print "[INFO]X $found_code, $this_quote_date, $first_quote, $last_quote\n";
	    next if ( Date_Cmp($this_quote_date,$first_quote) >= 0 and Date_Cmp($this_quote_date, $last_quote) <= 0);
	    #next if ( $found_code and ( $this_quote_date >= $first_quote and $this_quote_date <= $last_quote ))
	}

	#print "$date\t$stock_code\t$exchange\t$open\t$high\t$low\t$close\t$volume\n";
	print "[DEBUG]insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adj_close)\n" if ($debug);
	print "[DEBUG]'$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adj_close\n";
	$isth = $dbh->prepare("insert into quotes (date, symb, exch, open, high, low, close, volume, adj_close) values ('$date', '$stock_code', '$exchange', $open, $high, $low, $close, $volume, $adj_close)") or die $dbh->errstr;
	$isth->execute or die $dbh->errstr;
	$isth->finish;
    }
}
