<table id="select_portfolio_table">
<tr><th></th><th>Name</th><th>Exchange</th><th>Date</th></tr>
<?php
foreach ($portfolios as $portfolio)
{
    print '<tr><td></td><td>' . $portfolio->name . '</td><td>' . $portfolio->exch . '</td><td>' . $portfolio->working_date . '</td></tr>' . "\n";
}
?>
</table>
