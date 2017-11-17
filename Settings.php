<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 


class Settings {

    private $CI;
    public function __construct()
    {
        $this->CI=& get_instance();
        $this->CI->load->database();
    }

    function setValue($key,$value)
    {
        if($this->getKey($key))
        {
            $this->CI->db->where('setting_name', $key);
            $this->CI->db->update('tbl_settings',array('setting_value'=>$value));
        }
        else
        {
            $this->CI->db->insert('tbl_settings', array('setting_name'=>$key,'setting_value'=>$value));
        }
    }

    function getKey($key)
    {
        $this->CI->db->select('setting_name')->from('tbl_settings')->where('setting_name',$key);
        $query =  $this->CI->db->get();
        return $query->row() ? $query->row()->setting_name : "";
    }
    
    function getValue($key)
    {
        $this->CI->db->select('setting_value')->from('tbl_settings')->where('setting_name',$key);
        $query =  $this->CI->db->get();
        return $query->row() ? $query->row()->setting_value : "";
    }
}
