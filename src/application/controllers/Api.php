<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('cas');

        // Set user's selected language.
        if ($this->session->userdata('language'))
        {
            $this->config->set_item('language', $this->session->userdata('language'));
            $this->lang->load('translations', $this->session->userdata('language'));
        }
        else
        {
            $this->lang->load('translations', $this->config->item('language')); // default
        }
    }

    public function login($key){
        $this->model_login($key);
    }

    public function accessConfiguration($key){
        if($this->model_accessConfiguration($key)){
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($this->getConfig()), TRUE);
        }else{
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('status'=>'incorrect or outdated key')), TRUE);
        }
    }

    private function getConfig(){
        return array(
            'status' => 'ok',
            'config' => array(
                'ssh_usr'=>'test', 
                'ssh_pwd'=>'test'
                )
        );
    }

    public function update($code){

        if($code == 'fastdb'){
            try{
    
                $this->load->library('migration');
    
                if ( ! $this->migration->current()){
                    throw new Exception($this->migration->error_string());
                }
    
                $view = ['success' => TRUE];
            }catch (Exception $exc){
                $view = ['success' => FALSE, 'exception' => $exc->getMessage()];
            }
        }
        $this->load->view('general/update', $view);
    }

    private function model_login($key){
        $timestamp_datetime = new DateTime('NOW');
        $timestamp = $timestamp_datetime->format('Y-m-d H:i:s');
        $data = array(
            'access_key' => $key,
            'timestamp' => $timestamp 
        );
        $this->db->insert('fast_access', $data);
    }

    private function model_accessConfiguration($key){
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