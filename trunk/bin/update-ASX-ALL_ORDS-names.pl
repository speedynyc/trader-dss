#!/usr/bin/perl -w
# dump-cpan-modules-for-author - display modules a CPAN author owns
use LWP::Simple;
use URI;
use HTML::TableContentParser;
use HTML::Entities;
use DBI;
use strict;

our $base_URL = shift || 'http://au.finance.yahoo.com/q/cp?s=%5EAORD&c=';
my ($tcp, $page, $html, $tables, $table, $table_count, $row_count, $row, $col_count, $column, $cell);
my ($URL, $symb_already_in_db, $symb, $name, $sth, @row);

my $dbname   = 'trader';
my $username = 'postgres';
my $password = '';
my $total_added=0;
my $host     = $ARGV[0];

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname;host=$host", $username, $password) or die $DBI::errstr;

my $table_to_parse = 16;
my $exch = 'AX';

$tcp = HTML::TableContentParser->new();
foreach $page (0 .. 15)
{
    $URL = $base_URL . $page;
    $html = get($URL);
    next unless($html);
    $tables = $tcp->parse($html);
    $table_count = 1;
    for $table (@$tables) {
        if ($table_count == $table_to_parse)
        {
            $row_count = 0;
            for $row (@{$table->{rows}}) {
                if ($row_count > 0)
                {
                    $col_count = 0;
                    for $column (@{$row->{cells}})
                    {
                        if ($col_count == 0 or $col_count == 1)
                        {
                            $cell = $column->{data};
                            $cell =~ s/<(.*?)>//g;
                            if ($col_count == 0)
                            {
                                $symb = $cell;
                                # format comes in as ABC.AX, we need to get rid of the .AX
                                ($symb, undef) = split(/\./, $symb);
                            }
                            elsif ($col_count == 1)
                            {
                                $name = $cell;
                                # escape single quotes
                                $name =~ s/'/''/g;
                                $sth = $dbh->prepare("select symb from stocks where symb = \'$symb\' and exch = \'$exch\'");
                                $sth->execute or die $dbh->errstr;
                                $symb_already_in_db = 0;
                                while ((@row) = $sth->fetchrow_array)
                                {
                                    if ($row[0] eq $symb)
                                    {
                                        print "[INFO]Stock $symb , $name, $exch already in stocks\n";
                                        $symb_already_in_db = 1;
                                    }
                                }
                                if (not $symb_already_in_db)
                                {
                                    ++$total_added;
                                    print "[INFO]insert into stocks values(\'$symb\',\'$name\',\'$exch\')\n";
                                    $sth = $dbh->prepare("insert into stocks values(\'$symb\',\'$name\',\'$exch\')") or die $dbh->errstr;
                                    $sth->execute or die $dbh->errstr;
                                }
                                last;
                            }
                        }
                        $col_count++;
                    }                         
                }
                $row_count++;
            }
        }
        elsif ($table_count > $table_to_parse)
        {
            # go to the next page
            last;
        }
        $table_count++;
    }
}
print "[INFO]Total added $total_added\n";

