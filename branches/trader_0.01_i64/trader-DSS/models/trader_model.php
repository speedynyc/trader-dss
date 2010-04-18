<?php

class Trader_model extends Model
{
    function get_uid($username, $passwd)
    {
        $uid = -1;
        $query = $this->db->select('uid')->from('users')
            ->where('name', $username)
            ->where('passwd', md5($passwd))
            ->get();
        if ($query->num_rows() > 0)
        {
            $row = $query->row();
            $uid = $row->uid;
        }
        return $uid;
    }
}

?>
