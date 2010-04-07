#!/usr/bin/perl -w

# fetch all the symbols/names/sectors for all the shares.
# this is done by ripping out the data from a yahoo table

use HTML::TableExtract;
use LWP::UserAgent;
use Data::Dumper;
use DBI;
use strict;

my ($a, $b);
my $debug = 0;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = '';
my $total_added=0;
my $exch = 'L';
my ($line, $symb, $name);

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

# get the base one, then get all subsequent ones
my $stocks = '/tmp/stocks.txt';
open(STOCKS, "<$stocks") or die "[FATAL]Unable to open $stocks: $!\n";
while ($line = <STOCKS>)
{
    chomp($line);
    ($symb, $name, $exch) = split(/,/, $line);
    add_to_db($symb, $name, $exch);
}
	

sub add_to_db
{
    my $symb = shift;
    my $name = shift;
    my $exch = shift;
    $name =~ s/\'/\'\'/g;
    my ($sth, @row);
    #print "$symb, $exch, $name\n";
    $sth = $dbh->prepare("select symb from stocks where symb = \'$symb\' and exch = \'$exch\'");
    $sth->execute or die $dbh->errstr;
    while ((@row) = $sth->fetchrow_array)
    {
        if ($row[0] eq $symb)
        {
            print "[INFO]Stock $symb , $name, $exch already in stocks\n";
            return;
        }
    }
    ++$total_added;
    print "[INFO][Add Symb $total_added]$symb, $name, $exch\n";
    print "insert into stocks values(\'$symb\',\'$name\',\'$exch\')\n" if ($debug);
    $sth = $dbh->prepare("insert into stocks values(\'$symb\',\'$name\',\'$exch\')") or die $dbh->errstr;
    $sth->execute or die $dbh->errstr;
    $sth->finish;
}
