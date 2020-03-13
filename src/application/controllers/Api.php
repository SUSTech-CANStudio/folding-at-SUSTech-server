<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('api_model');
        $this->load->library('session');
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
        $this->api_model->login($key);
    }

    public function accessConfiguration($key){
        if($this->api_model->accessConfiguration($key)){
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
}