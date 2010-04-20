<table id="select_portfolio_table">
<tr><th></th><th>Name</th><th>Exchange</th><th>Date</th><th>Balance</th></tr>
<?php
foreach ($portfolios as $portfolio)
{
    print '<tr><td></td><td>' . $portfolio->getName() . '</td><td>' .
        $portfolio->getExch()->getName() . '</td><td>' .
        $portfolio->getWorkingDate() . '</td><td align="right">' . 
        $portfolio->getExch()->getCurrency() . sprintf("%.2f",$portfolio->getCashInHand() + $portfolio->getHoldings()) . '</td></tr>' . "\n";
}
?>
</table>
