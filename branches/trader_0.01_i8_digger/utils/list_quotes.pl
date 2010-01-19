#!/usr/bin/perl -w

# dump all the entries in the quotes table in comma delimited format for reading into a new instance


use strict;
use DBI;

my @date_results;
my $debug = 0;

my $dbname   = "trader";
my $username = "postgres";
my $password = "";

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

# query all the quotes ordered by symb and date
my $db_query = "select date, symb, exch, open, high, low, close, volume, adj_close from quotes order by symb, date;";
print "[INFO]db_query: $db_query\n" if ($debug);
my $dates_sth = $dbh->prepare($db_query) or die $dbh->errstr;
$dates_sth->execute or die $dbh->errstr;

# print out the results comma delimited
$, = ',';
while (@date_results = $dates_sth->fetchrow_array)
{
    print @date_results;
    print "\n";
}
$dbh->disconnect;
