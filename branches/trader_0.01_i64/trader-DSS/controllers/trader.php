<?php

class Trader extends Controller {

    function Trader()
    {
        parent::Controller();	
    }

    function index()
    {
		$this->login();
	}

    function login()
    {
        $data = array();
        $data['body_view'] = 'login_view';
        $this->load->view('templates/body', $data);
    }

    function check_login()
    {
        // check that the login info has been sent correctly
        $this->load->model('trader_model');
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        $uid = $this->trader_model->get_uid($username, $password);
        if ($uid > 0)
        {
            // save session cookie and move onto the portfolios page
            $cookie = array(
                    'username' => $username,
                    'uid' => $uid,
                    'is_logged_in' => true
                    );
            $this->session->set_userdata($cookie);
            #$this->portfolios();
            echo base_url() . '/trader/portfolios';
        }
        else
        {
            echo '<div style="background-color:#ffa; padding:5px">Login Failed</div>';
        }
    }

    function portfolios()
    {
        $data = array();
        $data['body_view'] = 'portfolios_view';
        $this->load->view('templates/body', $data);
    }
}

?>
