<?php $this->load->view('templates/header'); ?>
    <script type="text/javascript"> 
    // prepare the form when the DOM is ready 
    $(document).ready(function() { 
            // bind form using ajaxForm 
            $('#login_form').ajaxForm({ 
                // success identifies the function to invoke when the server response 
                // has been received; here we apply a fade-in effect to the new content 
                timeout: 3000,
                error: report_error,
                beforeSubmit: report_checking,
                success: report_status
            }); 
    });
    function report_checking(responseText, statusText, xhr, $form) {
        $('#login_status').html('Checking login credentials');
    }
    function report_error(responseText, statusText, xhr, $form) {
        $('#login_status').html('<font color=red>AJAX call failed</font>');
    }
    function report_status(responseText, statusText, xhr, $form) {
        if (/http/.test(responseText))
        {
            $('#login_status').html('Login Succesful, redirecting...');
            window.location = responseText;
        }
        else
        {
            $('#login_status').html(responseText);
        }
    };
    </script> 
    <?php
        $attributes = array('id' => 'login_form');
        echo form_open('trader/check_login', $attributes);
    ?>
    <table>
    <tr> <td colspan="2"><b>Login to the <a href="http://code.google.com/p/trader-dss/">Trader DSS</a>. Authorised users only</b></td> </tr>
    <tr>
        <td><b>Username:</b></td>
        <td>
        <?php $form_data = array(
            'name'          => 'username',
            'id'          => 'username',
            'style'       => 'width:80%',
            );
            echo form_input($form_data);
        ?>
        </td>
    </tr>
    <tr><td><b>Password:</b></td>
        <td>
        <?php $form_data['id'] = 'password';
            $form_data['name'] = 'password';
            echo form_password($form_data);
        ?>
        </td>
    </tr>
    <tr>
        <td align="center"><?php echo form_submit('login', 'Login'); ?></td>
        <td align="center"><?php echo anchor('trader/request_account', 'Request Account') ?></td>
    </tr>
    <tr>
        <td colspan="2"><div id="login_status"></div></td>
    </tr>
    </table>
    <?php form_close() ?>

<?php $this->load->view('templates/footer'); ?>
