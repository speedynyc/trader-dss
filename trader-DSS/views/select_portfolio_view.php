<script type="text/javascript"> 
// prepare the form when the DOM is ready 
$(document).ready(function() { 
    // bind form using ajaxForm 
    $('#portfolio_select_form').ajaxForm({ 
        // success identifies the function to invoke when the server response 
        // has been received; here we apply a fade-in effect to the new content 
        timeout: 3000,
        error: report_select_portfolio_error,
        success: update_select_portfolios_success
    }); 
    $('#use_pf').click(function() {
        $('#portfolio_select_form').ajaxSubmit({
            url: "/trader/set_portfolio",
            success: update_select_portfolios_success
        });
        return false;
    });
    $('#del_pf').click(function() {
        $('#portfolio_select_form').ajaxSubmit({
            url: "/trader/del_portfolio",
            success: update_select_portfolios_success
        });
        return false;
    });
});

function report_select_portfolio_error(responseText, statusText, xhr, $form) {
    $('#summary_table').html('<font color=red>AJAX call failed</font>');
}

function update_select_portfolios_success(responseText, statusText, xhr, $form) {
    // update the header with the new portfolio
    $.ajax({
        url: '/trader/get_summary_table',
        success: function(data) {
            $('#summary_table').html(data);
            update_tab_header();
        }
    });
    // redirect to query selectiion or report seccess?
}

</script>
<?php 
    $form_attributes = array('id' => 'portfolio_select_form');
    print form_open('/trader/set_portfolio', $form_attributes)
?>
<table id="select_portfolio_table">
<tr><th></th><th>Name</th><th>Exchange</th><th>Date</th><th>Balance</th></tr>
<?php
foreach ($portfolios as $portfolio)
{
    $radio_data = array(
        'name' => 'pfid_radio',
        'id'   => $portfolio->getID(),
        'value' => $portfolio->getID(),
        'checked' => false
        );
    print '<tr><td>' . form_radio($radio_data) . '</td><td>' .
        $portfolio->getName() . '</td><td>' .
        $portfolio->getExch()->getName() . '</td><td>' .
        $portfolio->getWorkingDate() . '</td><td align="right">' . 
        $portfolio->getExch()->getCurrency() . sprintf("%.2f",$portfolio->getCashInHand() + $portfolio->getHoldings()) . '</td></tr>' . "\n";
}
?>
<tr><td colspan="100">
<?php
$data = array(
        'name'        => 'use_pf',
        'id'          => 'use_pf',
        'value'       => 'Use'
        );
print form_submit($data);
$data = array(
        'name'        => 'del_pf',
        'id'          => 'del_pf',
        'value'       => 'Delete'
        );
print form_submit($data);
?>
</table>
<?php print form_close(); ?>
