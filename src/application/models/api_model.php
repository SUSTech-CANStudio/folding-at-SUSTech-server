<?php

class Api_model extends CI_Model{

    public function login($key){
        $timestamp_datetime = new DateTime('NOW');
        $timestamp = $timestamp_datetime->format('Y-m-d H:i:s');
        $data = array(
            'access_key' => $key,
            'timestamp' => $timestamp 
        );
        $this->db->insert('fast_access', $data);
    }

    public function accessConfiguration($key){
        $access = $this->db->select('
            fast_access.timestamp AS time
        ')
        ->from('fast_access')
        ->where('access_key', $key)
        ->get()
        ->row_array()['time'];

        // the key is not correct
        if($access == NULL){
            return false;
        }

        // calculate time diff
        $access_datetime = new DateTime($access);
        $now_datetime = new DateTime('NOW');
        $interval = $access_datetime->diff($now_datetime);
        $d_yea = $interval->format('%Y');
        $d_mon = $interval->format('%m');
        $d_day = $interval->format('%d');
        $d_hrs = $interval->format('%H');
        $d_min = $interval->format('%i');
        // if time diff is within one min, return key
        $rtn = $d_yea == 0 && $d_mon == 0 && $d_day == 0 && $d_hrs == 0 & $d_min == 0;     

        $this->db->where('access_key', $key);
        $this->db->delete('fast_access');

        return $rtn;
    }

}