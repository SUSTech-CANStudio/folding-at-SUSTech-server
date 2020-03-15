<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('cas');

    }

    public function login(){
        try{
            $usr_info = json_decode($this->input->post('usr_info'));
            $username = $usr_info->username;
            $password = $usr_info->password;
            $key      = $usr_info->key;

            if($this->model_verify('https://cas.sustech.edu.cn/cas/login', $username, $password)){
                $this->model_login($key);
            }

		}catch (Exception $exc){
			$this->output
				->set_content_type('application/json')
				->set_output(json_encode(['exceptions' => [exceptionToJavaScript($exc)]]));
        }
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
            'config' => "[Interface]\n
            PrivateKey = hhFgjPpnGevey2vmSdBQ4QjUhLXLkbJs5rLO96DJheg=\n
            Address = 10.183.34.212/8\n
            DNS = 1.1.1.1\n
            \n
            [Peer]\n
            PublicKey = FO1Hc6UeM0lG8fSxSZYm/ED/4hfTsJ3VcnM09uDtjzM=\n
            Endpoint = 190.2.141.162:51840\n
            AllowedIPs = 0.0.0.0/0
            "
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

    public function test(){
        echo $this->model_verify('https://cas.sustech.edu.cn/cas/login', '11710403', '0615**iuui')
            ? 'nb'
            : 'gg';
    }

    private function model_verify($url, $username, $password){

        $post = array(
            'username' => $username,
            'password' => $password,
            'execution' => $this->getExecution($url), 
            '_eventId' => 'submit',
            'locale' => 'en'
        );

        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); 
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); 
        curl_setopt($curl, CURLOPT_POST, 1); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); 
        curl_setopt($curl, CURLOPT_HEADER, 0); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        $res = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);
        }
        curl_close($curl); 
        return strpos($res, 'Log In Successful');
    }

    public function getExecution($url){
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, $url); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); 
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); 
        curl_setopt($curl, CURLOPT_HEADER, 0); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        $res = curl_exec($curl); 
        preg_match('/name="execution" value="[^ ]+"/', $res, $match);
        return substr($match[0], 24, strlen($match[0]) - 25);
    }
}