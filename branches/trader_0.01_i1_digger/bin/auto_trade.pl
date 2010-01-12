#!/usr/bin/perl -w

# buy according to the buy criteria, sell similarly and report profit

# $Header: /home/trader/bin/RCS/auto_trade.pl,v 1.2 2009/05/16 13:15:43 trader Exp trader $

use lib '/var/lib/pgsql/trader/current/modules';

use strict;
use Date::Manip;
use DBI;
#use CGI qw(:standard *table *Tr *td);
#use CGI::Pretty;
#use CGI::Carp qw/fatalsToBrowser/;
#use trader_cgi;
my $view_columns = '
symb          | character varying(10)  | 
symb_name     | character varying(100) | 
exch_name     | character varying(100) | 
date          | date                   | 
open          | numeric(9,2)           | 
high          | numeric(9,2)           | 
low           | numeric(9,2)           | 
close         | numeric(9,2)           | 
volume        | numeric(12,0)          | 
gain_10       | numeric(9,2)           | 
gain_20       | numeric(9,2)           | 
gain_30       | numeric(9,2)           | 
gain_50       | numeric(9,2)           | 
gain_100      | numeric(9,2)           | 
gain_200      | numeric(9,2)           | 
close_ma_10   | numeric(9,2)           | 
close_ma_20   | numeric(9,2)           | 
close_ma_30   | numeric(9,2)           | 
close_ma_50   | numeric(9,2)           | 
close_ma_100  | numeric(9,2)           | 
close_ma_200  | numeric(9,2)           | 
close_sd_10   | numeric(9,4)           | 
close_sd_20   | numeric(9,4)           | 
close_sd_30   | numeric(9,4)           | 
close_sd_50   | numeric(9,4)           | 
close_sd_100  | numeric(9,4)           | 
close_sd_200  | numeric(9,4)           | 
volume_sd_10  | numeric(9,4)           | 
volume_sd_20  | numeric(9,4)           | 
volume_sd_30  | numeric(9,4)           | 
volume_sd_50  | numeric(9,4)           | 
volume_sd_100 | numeric(9,4)           | 
volume_sd_200 | numeric(9,4)           |';

my (@stock_results, $start_date, @date_results);
my ($current_date, $buy_query_string, $sell_query_string);
my ($date, $symb, $close, $close_ma, $close_ma_diff, $symb_exch, $current_symbol, $current_exch);
# data structure to store what we own
# $holdings{"symb,exch"} = symbol,exch
# $holdings{"symb,exch"}{avg_p} = average price paid
# $holdings{"symb,exch"}{qty} = average price paid
# $holdings{"symb,exch"}{t_cost} = qty * avg_p
# $holdings{"symb,exch"}{c_val} = qty * close # the current market value
my ($number_we_can_buy, %holdings, $buy_sth, $sell_sth, $total_val, $assets);
my ($number_bought_today);

#my $debug = 2;
my $debug = 0;

my $dbname   = "trader";
my $username = "postgres";
my $password = "";

# money parameters
my $parcel = 1000;		    # the amount to trade in a single transaction
my $parcel_count = 1000;		    # the number of parcels to play with
my $pot = $parcel_count * $parcel;  # the total amount of money we start with
my $max_per_day = $parcel_count/10;		    # the max no of transactions in a day (might not be enough when $rebuy is false)
my $buy_ma_to_use  = 'close_ma_10'; # the moving average to compare against when buying
my $sell_ma_to_use = 'close_ma_10'; # the moving averate to compare against when selling
my $exch = 'L';			    # the exchange to trade on
my $rebuy = 1;			    # are we allowed to buy more of something we've already got?

my $days_to_trade = 0;
my $days_traded = 0;

# initialize Date::Manip and connect to the database
Date_Init("DateFormat=non-US");
my $dbh = DBI->connect("dbi:Pg:dbname=$dbname", $username, $password) or die $DBI::errstr;

# Number of months back to start
my $months_back = 12;
# end the simulation at today
my $today = ParseDate('12am today');

# get the dates into a format we can use with postgres
$start_date = DateCalc($today, "- $months_back months");
my $start_month = UnixDate($start_date, "%m");
my $start_day   = UnixDate($start_date, "%d");
my $start_year  = UnixDate($start_date, "%Y");
my $end_month = UnixDate($today, "%m");
my $end_day   = UnixDate($today, "%d");
my $end_year  = UnixDate($today, "%Y");
my $pg_start_date = "$start_year-$start_month-$start_day";
my $pg_end_date = "$end_year-$end_month-$end_day";

# buy criteria
my $buy_fields = "date, symb, close, $buy_ma_to_use,  \"close\" - $buy_ma_to_use as close_ma_diff";
my $where_clause = "close < $parcel and exch = \'$exch\'";
my $order_clause = 'close_ma_diff desc';
# sell criteria
my $sell_fields = "date, symb, close, $sell_ma_to_use,  \"close\" - $sell_ma_to_use as close_ma_diff";
my $count_limit = $parcel_count * $max_per_day; # only a guess but probably a good number to fetch each day

# query to find the trading dates between given dates
my $dates_query = "select date from trade_dates where date >= '$pg_start_date' and date <= '$pg_end_date' and exch = '$exch' order by date;";
print "[INFO]dates_query: $dates_query\n" if ($debug);
my $dates_sth = $dbh->prepare($dates_query) or die $dbh->errstr;
$dates_sth->execute or die $dbh->errstr;

# work through the dates in the range seeing which things to buy, sell and keep
while (@date_results = $dates_sth->fetchrow_array)
{
    # find what's not hot and sell it.
    # we sell first to avoid trying to sell what we've just bought
    foreach $symb_exch (keys(%holdings))
    {
        ($current_symbol, $current_exch) = split(/,/,$symb_exch);
        $sell_query_string = "select $sell_fields from view_everything where date = '$current_date' and symb = '$current_symbol' and exch = '$current_exch';\n";
        print "[DEBUG]sell_query_string $sell_query_string" if ($debug > 1);
        $sell_sth = $dbh->prepare($sell_query_string) or die $dbh->errstr;
        $sell_sth->execute or die $dbh->errstr;
        while (@stock_results = $sell_sth->fetchrow_array)
        {
            ($date, $symb, $close, $close_ma, $close_ma_diff) = @stock_results;
            print "[DEBUG]Sell candidate: date=$date, symb=$symb, close=$close, close_ma=$close_ma, close_ma_diff=$close_ma_diff \n" if ($debug > 1);
            if ($close_ma_diff <= 0)
            {
                # it's dropped to the MA, sell!
                print "[DEBUG]Sell $symb, $exch qty = $holdings{$symb_exch}{'qty'} at $close, $sell_ma_to_use = $close_ma, $close_ma_diff\n" if ($debug);
                $pot += $holdings{$symb_exch}{'qty'} * $close;
                delete($holdings{$symb_exch});
            }
            else
            {
                # record how much it's worth for later reporting
                print "[INFO]Keep $symb, $exch close = $close, $sell_ma_to_use = $close_ma\n" if ($debug);
                $holdings{$symb_exch}{'c_val'} = $holdings{$symb_exch}{'qty'} * $close;
            }
        }
    }
    # find what's hot today and buy it
    $number_bought_today = 0;
    $current_date = $date_results[0];
    print "[DEBUG]Trade date: $current_date\n" if ($debug);
    $buy_query_string = "select $buy_fields from view_everything where date = '$current_date' and $where_clause order by $order_clause limit $count_limit;\n";
    print "[INFO]buy_query_string: $buy_query_string" if ($debug > 1);
    $buy_sth = $dbh->prepare($buy_query_string) or die $dbh->errstr;
    $buy_sth->execute or die $dbh->errstr;
    while (@stock_results = $buy_sth->fetchrow_array)
    {
        next if ($number_bought_today >= $max_per_day);
        ($date, $symb, $close, $close_ma, $close_ma_diff) = @stock_results;
        print "[DEBUG]Buy candidate: date=$date, symb=$symb, close=$close, close_ma=$close_ma, close_ma_diff=$close_ma_diff \n" if ($debug > 1);
        $number_we_can_buy = int($parcel / $close);
        # ensure that we don't attempt to buy more that we can afford
        if ($number_we_can_buy * $close > $pot)
        {
            $number_we_can_buy = int($pot / $close);
        }
        if ($close_ma_diff > 0 and $number_we_can_buy > 0)
        {
            # buy $parcel's worth of it and give the change back to the pot
            if (exists($holdings{"$symb,$exch"}))
            {
                if ($rebuy)
                {
                    # buy more
                    print "[DEBUG]Rebuying($number_bought_today) $symb, $exch qty = $number_we_can_buy at $close. $buy_ma_to_use = $close_ma\n" if ($debug);
                    ++$number_bought_today;
                    $pot -= $number_we_can_buy * $close;
                    $holdings{"$symb,$exch"}{'avg_p'} = ($holdings{"$symb,$exch"}{'avg_p'} * $holdings{"$symb,$exch"}{'qty'} + $close * $number_we_can_buy) / ($holdings{"$symb,$exch"}{'qty'} + $number_we_can_buy);
                    $holdings{"$symb,$exch"}{'qty'} += $number_we_can_buy;
                    $holdings{"$symb,$exch"}{'t_cost'} = $holdings{"$symb,$exch"}{'avg_p'} * $holdings{"$symb,$exch"}{'qty'};
                    $holdings{"$symb,$exch"}{'c_val'} = $holdings{"$symb,$exch"}{'qty'} * $close;
                }
                else
                {
                    next;
                }
            }
            else
            {
                # buy the first lot
                print "[DEBUG]Buying($number_bought_today) $symb, $exch qty = $number_we_can_buy at $close. $buy_ma_to_use = $close_ma\n" if ($debug);
                ++$number_bought_today;
                $pot -= $number_we_can_buy * $close;
                $holdings{"$symb,$exch"}{'avg_p'} = $close;
                $holdings{"$symb,$exch"}{'qty'} = $number_we_can_buy;
                $holdings{"$symb,$exch"}{'t_cost'} = $holdings{"$symb,$exch"}{'avg_p'} * $holdings{"$symb,$exch"}{'qty'};
                $holdings{"$symb,$exch"}{'c_val'} = $holdings{"$symb,$exch"}{'qty'} * $close;
            }
        }
    }

    # report the pot and the assets
    $total_val = 0;
    foreach $symb_exch (keys(%holdings))
    {
        $total_val += $holdings{$symb_exch}{'c_val'};
    }
    $assets = $pot + $total_val;
    print "[INFO]$current_date, Holdings = $total_val, Pot = $pot, Total = $assets\n";
    ++$days_traded;
    if ($days_to_trade > 0 && $days_traded >= $days_to_trade)
    {
        print "[DEBUG]Exiting after $days_traded days\n" if ($debug);
        exit;
    }
}
$dbh->disconnect;
exit;
