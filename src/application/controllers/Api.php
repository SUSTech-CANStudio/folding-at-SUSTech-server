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
            }else{
                $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array("status"=>"wrongpwd")));
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
            'config' => "[Interface] # Cother\n# PublicKey = wnmLb+eRlMK2QEevrwgm92O3ufwnNSAEW5E3IZzdCVY=\nPrivateKey = 8P8XeMVaBNh0rmWCpZtiMAPSIOP4k3j2IbmswCK5klY=\n# Switch DNS server while connected\nDNS = 192.168.0.254\n# The addresses to bind to. Either IPv4 or IPv6. /31 and /32 are not supported.\nAddress = 10.89.65.101/32, fd50:6333:3140:feed::101/128\nObfuscateKey = babe\nObfuscateTCP = tls-chrome\n[Peer]\nPublicKey = JwofRfFBWKKtR49UksC8TGJm9np0sp0HnUCjwMYZeAc=\n# The IP range that we may send packets to for this peer.\nAllowedIPs = 0.0.0.0/0, ::/0\n# Address of the server\nEndpoint = tcp://hpe.sorz.org:51840\n# Send periodic keepalives to ensure connection stays up behind NAT.\nPersistentKeepalive = 60"
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