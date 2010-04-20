<?php

class Trader_portfolios_model extends Model
{
    function Trader_portfolios_model()
    {
        parent::Model();
    }

    function get_portfolios($uid)
    {
        $this->load->helper('trader');
        $portfolios = array();
        $query = $this->db->select('pfid')->from('portfolios')->where('uid', $uid)->get();
        foreach ($query->result() as $row)
        {
            $portfolios[] = new portfolio($row->pfid);
        }
        return $portfolios;
    }
}

?>
