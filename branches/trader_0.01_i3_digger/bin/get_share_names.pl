#!/usr/bin/perl -w

# fetch all the symbols/names/sectors for all the shares.
# this is done by ripping out the data from a yahoo table

# $Header: /var/lib/pgsql/bin/RCS/get_share_names.pl,v 1.2 2007/08/10 14:33:04 postgres Exp postgres $

use HTML::TableExtract;
use LWP::UserAgent;
use Data::Dumper;
use DBI;

$dbname   = 'trader';
$username = 'postgres';
$password = '';
#$password = "happy";
$total_added=0

$dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

# get the base one, then get all subsequent ones
$res = get_page("http://uk.biz.yahoo.com/p/uk/cpi/index.html");
get_companies($res);
@names = ('1', '2', '3', '4', '5', '6', '8', '@', 'q', 'x', 'y', 'z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'v', 'w');

foreach $a (@names)
{
    $res = 1;
    $b   = 0;
    while ($res)
    {
        $res = 0;
        $res = get_page("http://uk.biz.yahoo.com/p/uk/cpi/cpi${a}${b}.html");
        if ($res)
        {
            get_companies($res);
        }
        else
        {
            next;    # there won't be any beyond the last one!
        }
        $b++;
    }
}

print "[INFO]Total added $total_added\n";

$dbh->disconnect;

sub get_page
{
    # extract the company name from the given table
    my $page = shift;
    sleep 1;
    my ($res, $content);
    my $ua = new LWP::UserAgent;
    #$ua->proxy(['http'], 'http://web-proxy.britain.agilent.com:8088/');
    #$ua->proxy(['http'], 'http://bbnwebcache2.net.europe.agilent.com:8088/');
    $ua->agent("Mozilla/6.0");
    $res = $ua->get($page);
    if ($res->is_success)
    {
        $content = $res->content;
        print "[INFO]Got $page\n" if ($debug);
        return $content;
    }
    else
    {
        warn "[WARN]Couldn't get $page\n";
        return '0';
    }
}

sub get_companies
{
    my $content = shift;
    my ($symb, $name);
    $found_companies_table = 0;
    my $te = new HTML::TableExtract(depth => 0, count => 3, gridmap => 0);
    foreach $a (0 .. 10)
    {
        foreach $b (0 .. 10)
        {
            $te = HTML::TableExtract->new(depth => $a, count => $b);
            $te->parse($content);
            foreach $ts ($te->tables)
            {
                foreach $row ($ts->rows)
                {
                    #print "[INFO]table $a, $b: row is '$$row[0]'\n";
                    if ($$row[0] eq 'Companies')
                    {
                        $found_companies_table = 1;
                        next;
                    }
                    if ($found_companies_table)
                    {
                        print "   ", join(',', @$row), "\n" if ($found_companies_table and $debug);
                        ($name, $symb, undef) = (@$row);
                        $symb =~ m/(\S+)\.L/;
                        $symb = $1;
                        add_to_db($symb, $name);
                    }
                }
                last if ($found_companies_table);
            }
            last if ($found_companies_table);
        }
        last if ($found_companies_table);
    }
}

sub add_to_db
{
    my $symb = shift;
    #$symb = quotemeta($symb);
    my $exch = 'L';
    my $name = shift;
    $name =~ s/\'/\'\'/g;
    my $sth;
    print "$symb, $exch, $name\n";
    $sth = $dbh->prepare("select symb from stocks where symb = \'$symb\'");
    $sth->execute or die $dbh->errstr;
    while ((@row) = $sth->fetchrow_array)
    {
        if ($row[0] eq $symb)
        {
            print "[INFO]Stock $symb already in stocks\n";
            return;
        }
    }
    #print "insert into stocks values(\'$symb\',\'$name\',\'$exch\')\n" if ($debug);
    print "insert into stocks values(\'$symb\',\'$name\',\'$exch\')\n";
    $sth = $dbh->prepare("insert into stocks values(\'$symb\',\'$name\',\'$exch\')") or die $dbh->errstr;
    $sth->execute or die $dbh->errstr;
    $sth->finish;
    ++$total_added;
}
