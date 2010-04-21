<script>
    $(document).ready(function() {
        update_summary_header();
    });
    function update_summary_header(responseText, statusText, xhr, $form)
    {
        $('#summary_table').load('/trader/get_summary_table');
    }
</script>
<?php

// dray the header with a table linking the trader pages like tabs in a notebook
// session infomation is used to communicate between the tabs
$active_page = strtolower($active_page);
$active_colour = '#b1b1b1';
$inactive_colour = 'grey';
if (! isset($uid))
{
    // don't allow other page links if uid isn't set
    $active_page = 'login';
    $allow_others = false;
}
elseif (! $pfid)
{
    // uid is set but pf id isn't. Only allowable active page is 'login' or 'portfolio'
    if ($active_page != 'login' and $active_page != 'portfolios')
    {
        // force to the portfolio page
        $active_page = 'portfolios';
    }
    $allow_others = false;
}
switch ($active_page) {
    case 'login':
        print "<html><title>Trader Login</title><body>\n";
        break;
    case 'portfolios':
        print "<html><title>Select or Create a Portfolio</title><body>\n";
        break;
    case 'booty':
        print "<html><title>Report Portfolio Performance</title><body>\n";
        break;
    case 'select':
        print "<html><title>Choose Securities to trade</title><body>\n";
        break;
    case 'trade':
        print "<html><title>Buy selected Securities</title><body>\n";
        break;
    case 'watch':
        print "<html><title>Watch with a view to buy</title><body>\n";
        break;
    case 'queries':
        print "<html><title>Create queries to find securities</title><body>\n";
        break;
    case 'chart':
        print "<html><title>Inspect all symbols</title><body>\n";
        break;
    case 'history':
        print "<html><title>Historical trades</title><body>\n";
        break;
    case 'docs':
        print "<html><title>Documentation of the Trader Relations</title><body>\n";
        break;
    default:
        tr_warn("[FATAL]Cannot create header, given $active_page\n");
        $active_page = 'login';
        $allow_others = false;
        break;
}
?>

<table border="1" cellpadding="5" cellspacing="0" width="100%" align="center" class="nbb">
<tr><td colspan="100" valign="bottom" bgcolor="<?php echo $inactive_colour?>" class="nbb"><h1 style="font-family:verdana">Trader DSS</h1></td></tr><tr>

<?php
draw_cell('login', '/trader/login', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('portfolios', '/trader/portfolios', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('booty', '/trader/booty', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('select', '/trader/select', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('trade', '/trader/trade', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('watch', '/trader/watch', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('queries', '/trader/queries', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('chart', '/trader/chart', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('history', '/trader/history', $active_page, $active_colour, $inactive_colour, $allow_others);
draw_cell('docs', '/trader/docs', $active_page, $active_colour, $inactive_colour, $allow_others);
?>

</tr></table></table>
<div id=summary_table>
<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
<td><div id="summary_pf_name"></div></td>
<td><div id="summary_pf_working_date"></div></td>
<td><div id="summary_pf_exchange"></div></td>
<td><div id="summary_pf_gain"></div></td>
<td><div id="summary_pf_cash_in_hand"></div></td>
<td><div id="summary_query_name"></div></td>
</div>
</table>

<?php
function draw_cell($cell_desc, $cell_link, $active_page, $active_colour, $inactive_colour, $cell_selectable)
{
    if ($cell_desc == $active_page)
    {
        print "<td bgcolor=\"$active_colour\" class=\"nbb\">";
        if ($cell_selectable or $cell_desc == 'login')
        {
            print "<a href=\"$cell_link\">$cell_desc</a>";
        }
        else
        {
            print "$cell_desc";
        }
    }
    else
    {
        if ($cell_selectable or $cell_desc == 'login')
        {
            print "<td bgcolor=\"$inactive_colour\" class=\"sb\" onclick=window.location=\"$cell_link\" >";
            print "<a href=\"$cell_link\">$cell_desc</a>";
        }
        else
        {
            print "<td bgcolor=\"$inactive_colour\" class=\"sb\">";
            print "$cell_desc";
        }
    }
    print "</td>\n";
}

?>
</div>
