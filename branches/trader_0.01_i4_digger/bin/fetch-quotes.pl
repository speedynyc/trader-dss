#!/usr/bin/perl -w

# fetch all the quotes, splits and dividends for all symbols listed in stocks.

# $Header: /home/trader/bin/RCS/fetch-quotes.pl,v 1.2 2010/01/03 10:12:55 trader Exp trader $

#use strict;
use LWP::UserAgent;
use Finance::QuoteHist::Yahoo;
use DBI;
use strict;

$| = 1;

my $dbname   = 'trader';
my $username = 'postgres';
my $password = '';
my (%stock, %start_dates, %splits, %dividends, $date, $symbol);
my ($row, @row, $exchange, $stock_code, $isth, $dividend, $added);
my ($pre, $post, $content, $url, $start_month, $start_day, $start_year);
my ($end_month, $end_day, $end_year);
my ($open, $close, $high, $low, $volume);

my $dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

my $useragent = LWP::UserAgent->new;

if ( ! -f "stock" )
{
    open(STOCK, ">stock") or die "unable to open stock: $!\n";
    print STOCK "#date\tstock_code\texchange\topen\thigh\tlow\tclose\tvolume\n";
}
else
{
    open(STOCK, "+>>stock") or die "unable to open stock: $!\n";
    load_stock();
}

if ( ! -f "start_dates" )
{
    open(START, ">start_dates") or die "unable to open start_dates: $!\n";
    print START "#stock_code\texchange\tdate\n";
}
else
{
    open(START, "+>>start_dates") or die "unable to open start_dates: $!\n";
    load_start_dates();
}

if ( ! -f "splits" )
{
    open(SPLITS, ">splits") or die "unable to open splits: $!\n";
    print SPLITS "#stock_code\texchange\tdate\tpost\tpre\n";
}
else
{
    open(SPLITS, "+>>splits") or die "unable to open splits: $!\n";
    load_splits();
}

if ( ! -f "dividends" )
{
    open(DIVIDENDS, ">dividends") or die "unable to open dividends: $!\n";
    print DIVIDENDS "#stock_code\texchange\tdate\tdividend\n";
}
else
{
    open(DIVIDENDS, "+>>dividends") or die "unable to open dividends: $!\n";
    load_dividends();
}

my $sth = $dbh->prepare("select symb from stocks where exchange = 'L' order by symb") or die $dbh->errstr;
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
    print "[INFO]Checking $stock_code\n";
    next if (exists($stock{$stock_code}));

    # get the yahoo summary page because it has the date range available for a stock
    print "[INFO] http://finance.yahoo.com/q/hp?s=$stock_code.$exchange\n";
    sleep 30;
    my $response = $useragent->get("http://finance.yahoo.com/q/hp?s=$stock_code.$exchange");
    if ($response->is_success)
    {
        $content = $response->content;
        # parse the date range out of the URL matching the following
        $content =~ m#(http://ichart.finance.yahoo.com/table.csv\S*)>\s*Download To Spreadsheet#;
        $url = $1;
        if ($url)
        {
            print "[INFO]Found CSV at $url\n";
        }
        else
        {
            print "[INFO]No csv offered for $stock_code\n";
            next; # didn't find any offer of a CSV so move right along
        }
        $url =~ m/a=(\d+)/;
        $start_month = $1;
        $start_month++;
        $url =~ m/b=(\d+)/;
        $start_day = $1;
        $url =~ m/c=(\d+)/;
        $start_year = $1;
        $url =~ m/d=(\d+)/;
        $end_month = $1;
        $end_month++;
        $url =~ m/e=(\d+)/;
        $end_day = $1;
        $url =~ m/f=(\d+)/;
        $end_year = $1;
        print "HERE 1\n";
        print START "$stock_code\t$exchange\t$start_day/$start_month/$start_year\n" unless (exists($start_dates{$stock_code}));
        my $q = new Finance::QuoteHist::Yahoo(
                symbols    => [qq($stock_code.$exchange)],
                start_date => "01/01/1970",
                end_date   => 'today',
                verbose    => 1
                );
        print "HERE 2\n";
        $q->adjusted(0);
        print "HERE 3\n";
        $added = 0;
        foreach $row ($q->quotes())
        {
            print "HERE 4\n";
            ($symbol, $date, $open, $high, $low, $close, $volume, undef) = @$row;
            if (! $added )
            {
                print "[INFO]Quote $date\t$stock_code\t$exchange\t$open\t$high\t$low\t$close\t$volume\n";
                $added = 1;
            }
            print STOCK "$date\t$stock_code\t$exchange\t$open\t$high\t$low\t$close\t$volume\n";
            $isth = $dbh->prepare("insert into quotes values ('$date', '$stock_code', 'L', $open, $high, $low, $close, $volume)") or die $dbh->errstr;
            $isth->execute or die $dbh->errstr;
            $isth->finish;
        }
        print "HERE 5\n";
        # Splits
        $added = 0;
        foreach $row ($q->splits())
        {
            ($symbol, $date, $post, $pre) = @$row;
            if (! $added)
            {
                print "[INFO]Split $stock_code\t$exchange\t$date\t$post\t$pre\n";
                $added = 1;
            }
            print SPLITS "$stock_code\t$exchange\t$date\t$post\t$pre\n";
            $isth = $dbh->prepare("insert into splits values ('$date', '$stock_code', 'L', '$date', $dividend)") or die $dbh->errstr;
            $isth->execute or die $dbh->errstr;
            $isth->finish;
        }
        # Dividends
        $added = 0;
        foreach $row ($q->dividends())
        {
            ($symbol, $date, $dividend) = @$row;
            if ( ! $added )
            {
                print "[INFO]Dividends $stock_code\t$exchange\t$date\t$dividend\n";
                $added = 1;
            }
            print DIVIDENDS "$stock_code\t$exchange\t$date\t$dividend\n";
            $isth = $dbh->prepare("insert into dividend values ('$stock_code', 'L', '$date', $dividend)") or die $dbh->errstr;
            $isth->execute or die $dbh->errstr;
            $isth->finish;
        }

    }
    else
    {
        warn "[WARN] failed to retrieve http://finance.yahoo.com/q/hp?s=$stock_code.$exchange\n";
    }
    last;
}
$sth->finish;
$sth->disconnect;

sub load_stock
{
    # load the stock names into the %stock hash
    my ($line, $code);
    while ($line = <STOCK>)
    {
        next if ($line =~ /^#/);
        (undef, $code, undef) = split(/\t/, $line);
        if (not exists($stock{$code}))
        {
            print "$code ";
            $stock{$code} = 1;
        }
    }
    print "\n";
}

sub load_start_dates
{
    my ($line, $code);
    while ($line = <START>)
    {
        next if ($line =~ /^#/);
        ($code, undef) = split(/\t/, $line);
        $start_dates{$code} = 1;
    }
}

sub load_splits
{
    my ($line, $code);
    while ($line = <SPLITS>)
    {
        next if ($line =~ /^#/);
        ($code, undef) = split(/\t/, $line);
        $splits{$code} = 1;
    }
}

sub load_dividends
{
    my ($line, $code);
    while ($line = <DIVIDENDS>)
    {
        next if ($line =~ /^#/);
        ($code, undef) = split(/\t/, $line);
        $dividends{$code} = 1;
    }
}
