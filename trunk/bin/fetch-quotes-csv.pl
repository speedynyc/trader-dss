#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.

#use strict;
use LWP::UserAgent;
use DBI;

$| = 1;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = '';
my (%stock, %start_dates, %splits, %dividends);
my $data_dir='/postgres/downloads';
my $stock_file = "$data_dir/stock";

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

my $useragent = LWP::UserAgent->new;

if ( ! -f $stock_file )
{
    open(STOCK, ">$stock_file") or die "unable to open $stock_file: $!\n";
    print STOCK "#date\tstock_code\texchange\topen\thigh\tlow\tclose\tvolume\n";
}
else
{
    open(STOCK, "<$stock_file") or die "unable to open $stock_file: $!\n";
    load_stock();
}

$sth = $dbh->prepare("select symb from stocks order by symb") or die $dbh->errstr;
$sth->execute or die $dbh->errstr;
while (@row = $sth->fetchrow_array)
{
    if ( -f "stop" )
    {
        print "[INFO]Exiting on stop file\n";
        exit;
    }
    $useragent->timeout('20');
    $stock_code = $row[0];
    $exchange   = 'L';
    $stock_code = "$stock_code.$exchange";
    print "[INFO]Checking $stock_code\n";
    next if (exists($stock{$stock_code}));

    # get the yahoo summary page because it has the date range available for a stock
    print "[INFO] http://finance.yahoo.com/q/hp?s=$stock_code.\n";
    sleep 30;
    $response = $useragent->get("http://finance.yahoo.com/q/hp?s=$stock_code");
    if ($response->is_success)
    {
        $content = $response->content;
        # parse the date range out of the URL matching the following
        $content =~ m#(http://ichart.finance.yahoo.com/table.csv\S*)>\s*Download To Spreadsheet#;
        $url = $1;
        if ($url)
        {
            print "[INFO]Found CSV at $url\n";
            $useragent->get("http://ichart.finance.yahoo.com/table.csv?s=$stock_code&ignore=.csv", ':content_file'=>"$data_dir/$stock_code.csv");
            print STOCK "$stock_code\n";
        }
        else
        {
            print "[INFO]No csv offered for $stock_code\n";
            next; # didn't find any offer of a CSV so move right along
        }
    }
    else
    {
        warn "[WARN] failed to retrieve http://finance.yahoo.com/q/hp?s=$stock_code\n";
    }
}
close(STOCK);
$sth->finish;

sub load_stock
{
    # load the stock names into the %stock hash
    my $line;
    while ($line = <STOCK>)
    {
        next if ($line =~ /^#/);
        chomp($line);
        $code = $line;
        if (not exists($stock{$code}))
        {
            print "$code ";
            $stock{$code} = 1;
        }
    }
    print "\n";
}
