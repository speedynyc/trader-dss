<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<meta name="robots" content="no-cache" />
<meta name="description" content="Trader-DSS" />
<meta name="keywords" content="stock trading, technical analysis, back testing" />
<meta name="robots" content="no-cache" />
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title id="page_title">Trader-DSS: Supporting trading decisions with analysis since 2010</title>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="<?php echo base_url();?>css/trader.css" rel="stylesheet" type="text/css" media="all" />
<script src="<?php echo base_url();?>css/jquery-1.4.2.js" type="text/javascript" charset="utf-8"></script>	
<script src="<?php echo base_url();?>css/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>	
<script src="<?php echo base_url();?>css/jquery.form.js" type="text/javascript" charset="utf-8"></script>	
<script>
    // update the summary header if the div exists
    $(document).ready(function() {
        update_summary_header();
    });
    function update_summary_header(responseText, statusText, xhr, $form)
    {
        $('#summary_table').load('/trader/get_summary_table');
    }
    function update_tab_header()
    {
        $('#tab_header').load('/trader/get_tab_header');
    }
</script>
</head>
<html>
<!-- the tab header comes next (usually) -->
<div id="tab_header">
