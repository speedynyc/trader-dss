#!/usr/bin/perl -w

# fetch all the symbols/names/sectors for all the shares.
# this is done by ripping out the data from a yahoo table

use HTML::TableExtract;
use LWP::UserAgent;
use Data::Dumper;

#$base_page = "http://uk.biz.yahoo.com/p/uk/cpi/index.html";
#$base_page = "http://uk.biz.yahoo.com/p/uk/cpi/cpia0.html";
$base_page = "http://finance.yahoo.com/q/hp?s=RBS.L";
my $ua = new LWP::UserAgent;
my $res = $ua->get($base_page);
if ($res->is_success)
{
    $content = $res->content;
}
else
{
    die "[FATAL]Couldn't get $base_page\n";
}

print "*************************************************************************\n";
print "[INFO] $base_page\n";
#print "$content\n";
print "*************************************************************************\n";

foreach $a ( 0 .. 10 )
{
    foreach $b ( 0 .. 10)
    {
        $te = HTML::TableExtract->new( depth => $a, count => $b );
        $te->parse($content);
        foreach $ts ($te->tables)
        {
            print "Table found at ", join(',', $ts->coords), ":\n";
            foreach $row ($ts->rows)
            {
                print "   ", join(',', @$row), "\n";
            }
        }
    }
}
