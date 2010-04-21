<?php
    $this->load->view('templates/header');
    $tab_data = array();
    $tab_data['active_page'] = $active_page;
    $tab_data['allow_others'] = true;
    if ($pfid == '')
    {
        // don't allow other tabs if a portfolio hasn't been selected
        $tab_data['allow_others'] = false;
    }
    $tab_data['uid'] = $uid;
    $tab_data['username'] = $username;
    $this->load->view('templates/tab_header', $tab_data);
?>
<script>
    $(document).ready(function() {
        $("#portfolio_tabs").tabs();
    });
</script>

<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td>
<div id="portfolio_tabs">
    <ul>
        <li><a href="/trader/get_portfolios"><span>Select Portfolio</span></a></li>
        <li><a href="#create_portfolio"><span>Create Portfolio</span></a></li>
    </ul>
    <div id="create_portfolio">
        Create a portfolio here
    </div>
</div>
</td></tr></table>
<?php $this->load->view('templates/footer'); ?>
