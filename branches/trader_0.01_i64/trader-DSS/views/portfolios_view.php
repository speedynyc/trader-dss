<?php
    $this->load->view('templates/header');
    $tab_data = array();
    $tab_data['active_page'] = $active_page;
    $tab_data['allow_others'] = 'false';
    $tab_data['uid'] = $uid;
    $tab_data['username'] = $username;
    $this->load->view('templates/tab_header', $tab_data);
?>
<script>
    $(document).ready(function() {
        $("#portfolio_tabs").tabs();
        update_select_portfolios();
    });

    function update_select_portfolios(responseText, statusText, xhr, $form)
    {
        $('#select_portfolio').load('/trader/get_portfolios');
    }
</script>
<table border="1" cellpadding="5" cellspacing="0" align="center"><tr><td>
<div id="portfolio_tabs">
    <ul>
        <li><a href="#select_portfolio"><span>Select Portfolio</span></a></li>
        <li><a href="#create_portfolio"><span>Create Portfolio</span></a></li>
    </ul>
    <div id="select_portfolio">
        Select the portfolio here
    </div>
    <div id="create_portfolio">
        Create a portfolio here
    </div>
</div>
</td</tr></table>
<?php $this->load->view('templates/footer'); ?>
