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
        $data['username'] = $this->session->userdata('username');
        $this->session->sess_destroy();
        $this->load->view('login_view', $data);
    }

    function check_login()
    {
        // check that the login info has been sent correctly
        $this->load->model('trader_login_model');
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        $uid = $this->trader_login_model->get_uid($username, $password);
        if ($uid > 0)
        {
            // save session cookie and move onto the portfolios page
            $cookie = array(
                    'username' => $username,
                    'uid' => $uid,
                    'is_logged_in' => true
                    );
            $this->session->set_userdata($cookie);
            // return the url to be redirected to
            echo base_url() . '/trader/portfolios';
        }
        else
        {
            // return the error message html
            echo '<div style="background-color:#ffa; padding:5px">Login Failed</div>';
        }
    }

    function is_logged_in()
    {
        $uid = $this->session->userdata('uid');
        if ($uid == '')
        {
            $this->login();
            return false;
        }
        return true;
    }

    function headers($active_page = '')
    {
        $this->session->set_userdata('active_page', $active_page);
        $this->load->view('templates/header');
        $this->load->view('templates/tab_header');
        $this->get_summary_table();
    }

    function portfolios()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('portfolios');
            $this->load->view('portfolios_view');
            $this->load->view('templates/footer');
        }
    }

    function get_portfolios()
    {
        // intended to be called via AJAX to return the selection table for portfolios
        $this->load->model('Trader_portfolios_model');
        $uid = $this->session->userdata('uid');
        $data['portfolios'] = $this->Trader_portfolios_model->get_portfolios($uid);
        $this->load->view('select_portfolio_view', $data);
    }

    function get_tab_header()
    {
        $this->load->view('templates/tab_header');
    }

    function get_summary_table()
    {
        // intended to be called via AJAX to return the the currently active portfolio
        $data = array();
        $pfid = $this->session->userdata('pfid');
        if ($pfid != '')
        {
            $portfolio = new portfolio($pfid);
            $data['summary_pf_name'] = $portfolio->getName();
            $data['summary_pf_working_date'] = $portfolio->getWorkingDate();
            $data['summary_pf_exchange'] = $portfolio->getExch()->getName();
            $currency = $portfolio->getExch()->getCurrency();
            $gain = $portfolio->dayGain(1);
            $formatted_gain = sprintf("%s%.2f", $currency, $gain);
            if ( $gain > 0 )
            {
                $data['summary_pf_gain'] = '<font color="green">' . $formatted_gain . '</font>';
            }
            elseif ( $gain < 0 )
            {
                $data['summary_pf_gain'] = '<font color="red">' . $formatted_gain . '</font>';
            }
            else
            {
                $data['summary_pf_gain'] = $formatted_gain;
            }
            $cash_in_hand = $portfolio->getCashInHand();
            $formatted_CIH = sprintf("%.2f", $portfolio->getCashInHand());
            $data['summary_pf_cash_in_hand'] = "$currency$formatted_CIH";
        }
        else
        {
            $data['summary_pf_name'] = '';
            $data['summary_pf_working_date'] = '';
            $data['summary_pf_exchange'] = '';
            $data['summary_pf_gain'] = '';
            $data['summary_pf_cash_in_hand'] = '';
        }
        $data['summary_query_name'] = $this->get_summary_query_name();
        $this->load->view('templates/header_summary_view', $data);
    }

    function get_summary_query_name()
    {
        // intended to be called via AJAX to return the the currently active portfolio
        $qid = $this->session->userdata('qid');
        if ($qid != '')
        {
            return 'Query Name';
        }
        return 'QN';
    }

    function set_portfolio()
    {
        $pfid = $this->input->post('pfid_radio');
        if ($pfid != '')
        {
            $this->session->set_userdata('pfid', $pfid);
        }
    }

    function booty()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('booty');
            $this->load->view('booty_view');
            $this->load->view('templates/footer');
        }
    }

    function select()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('select');
            $this->load->view('select_view');
            $this->load->view('templates/footer');
        }
    }

    function trade()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('trade');
            $this->load->view('trade_view');
            $this->load->view('templates/footer');
        }
    }

    function watch()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('watch');
            $this->load->view('watch_view');
            $this->load->view('templates/footer');
        }
    }

    function queries()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('queries');
            $this->load->view('queries_view');
            $this->load->view('templates/footer');
        }
    }

    function chart()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('chart');
            $this->load->view('chart_view');
            $this->load->view('templates/footer');
        }
    }

    function history()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('history');
            $this->load->view('history_view');
            $this->load->view('templates/footer');
        }
    }

    function docs()
    {
        // check logged in
        if ($this->is_logged_in())
        {
            $this->headers('docs');
            $this->load->view('docs_view');
            $this->load->view('templates/footer');
        }
    }

}

?>
