<?php

class Trader_portfolios_model extends Model
{
    function Trader_portfolios_model()
    {
        parent::Model();
    }

    function get_portfolios($uid)
    {
        $query = $this->db->from('portfolios')->where('uid', $uid)->get();
        return $query->result();
    }
}

?>
