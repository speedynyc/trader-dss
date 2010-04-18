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
        $data['username'] = $this->session->userdata('username');
        $this->session->sess_destroy();
        $data = array();
        $this->load->view('login_view', $data);
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
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'portfolios';
        $this->load->view('portfolios_view', $data);
    }

    function booty()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'booty';
        $this->load->view('portfolios_view', $data);
    }

    function select()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'select';
        $this->load->view('portfolios_view', $data);
    }

    function trade()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'trade';
        $this->load->view('portfolios_view', $data);
    }

    function watch()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'watch';
        $this->load->view('portfolios_view', $data);
    }

    function queries()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'queries';
        $this->load->view('portfolios_view', $data);
    }

    function chart()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'chart';
        $this->load->view('portfolios_view', $data);
    }

    function history()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'history';
        $this->load->view('portfolios_view', $data);
    }

    function docs()
    {
        $data = array();
        $data['uid'] = $this->session->userdata('uid');
        $data['pfid'] = $this->session->userdata('pfid');
        $data['username'] = $this->session->userdata('username');
        $data['active_page'] = 'docs';
        $this->load->view('portfolios_view', $data);
    }

}

?>
