#!/usr/bin/perl -w
# dump-cpan-modules-for-author - display modules a CPAN author owns
use LWP::Simple;
#use URI;
#use HTML::TableContentParser;
#use HTML::Entities;
use HTML::TableExtract;
use DBI;
use strict;

our $base_URL = shift || 'http://www.findata.co.nz/Markets/ASX/';
my ($tcp, $page, $html, $tables, $table, $table_count, $row_count, $row, $col_count, $column, $cell);
my ($URL, $symb_already_in_db, $symb, $name, $sth, @row, @dbrow);

my $dbname   = 'trader';
my $username = 'postgres';
my $password = '';
my $total_added=0;
my $host     = $ARGV[0];

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname;host=$host", $username, $password) or die $DBI::errstr;

my $table_to_parse = 16;
my $exch = 'AX';

my $te = HTML::TableExtract->new( headers => [qw(Code Name High Low Close Volume Change)] );
my $ts;

foreach $page ('A' .. 'Z')
{
    $URL = $base_URL . $page . '.htm';
    print "$URL\n";
    $html = get($URL);
    next unless($html);
    $te->parse($html);
    # Examine all matching tables
    foreach $ts ($te->tables)
    {
        print "Table (", join(',', $ts->coords), "):\n";
        foreach $row ($ts->rows) {
            $symb = @$row[0];
            $name = @$row[1];
            # escape single quotes
            $name =~ s/'/''/g;
            $sth = $dbh->prepare("select symb from stocks where symb = \'$symb\' and exch = \'$exch\'");
            $sth->execute or die $dbh->errstr;
            $symb_already_in_db = 0;
            while ((@dbrow) = $sth->fetchrow_array)
            {
                if ($dbrow[0] eq $symb)
                {
                    print "[INFO]Stock $symb , $name, $exch already in stocks\n";
                    $symb_already_in_db = 1;
                }
            }
            if (not $symb_already_in_db)
            {
                ++$total_added;
                print "[INFO] $total_added insert into stocks values(\'$symb\',\'$name\',\'$exch\')\n";
                $sth = $dbh->prepare("insert into stocks values(\'$symb\',\'$name\',\'$exch\')") or die $dbh->errstr;
                $sth->execute or die $dbh->errstr;
            }
        }
    }
}
print "[INFO]Total added $total_added\n";

