<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function login(){
        try{
            $usr_info = json_decode($this->input->post('usr_info'));
            $username = $usr_info->username;
            $password = $usr_info->password;

            if($this->model_verify('https://cas.sustech.edu.cn/cas/login', $username, $password)){
                $this->model_registerIfNot($username);
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array("status"=>"ok")));
            }

		}catch (Exception $exc){
			$this->output
				->set_content_type('application/json')
				->set_output(json_encode(['exceptions' => [exceptionToJavaScript($exc)]]));
        }
    }

    /**
     * ip          := model_getIp
     * private_key := _genKeyPair()['prikey']
     * public_key  := _genKeyPair()['pubkey']
     * 
     * send private_key and ip to the client
     * append public key and ip to tunsafe config
     * 
     */
    public function accessConfiguration(){

        try{
            
            $usr_info = json_decode($this->input->post('usr_info'));
            $username = $usr_info->username;
            $password = $usr_info->password;
            $sid = $username;

            if(!$this->model_verify('https://cas.sustech.edu.cn/cas/login', $username, $password)){
                $this->output
				->set_content_type('application/json')
                ->set_output(json_encode(array("status"=>"wrong password or username")));
                return;
            }

            $row = $this->model_getinfo($sid);

            if($client_ip){
                
            
                $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode($this->_getConfig(long2ip($row['ip']), $row['p6'])), TRUE);
            }else{
                $this->output
				->set_content_type('application/json')
				->set_output(json_encode(array("status"=>"user $sid is not registered to the server yet.")));
            }

        }catch (Exception $exc){
			$this->output
				->set_content_type('application/json')
				->set_output(json_encode(['exceptions' => [exceptionToJavaScript($exc)]]));
        }
    }

    private function _getConfig($client_ip, $privateKey){

        return array(
            'status' => 'ok',
            'config' => "[Interface]\nPrivateKey = ".$privateKey."\nAddress = ".$client_ip."/32\nObfuscateKey = babe\nObfuscateTCP = tls-chrome\n\n[Peer]\nPublicKey = eNj6FEzWMnO6z1Js4iPIp936C07bXV6Ja2Pav5dnuWo=\nAllowedIPs = 172.31.11.240/32, 65.254.110.245/32, 18.218.241.186/32, 155.247.0.0/16, 128.252.0.0/24, 192.31.46.0/24, 140.163.0.0/16\nEndpoint = tcp://folding-acc.citric-acid.zzwcdn.com:51820\nPersistentKeepalive = 60\n"
        );
    }

    private function _appendConfig($client_ip, $client_pubkey){
        $file = fopen('/etc/tunsafe/folding.conf', 'a'); // TODO /etc/tunsafe/folding.conf
        $append_txt = "\n[Peer]\nPublicKey = ".$client_pubkey."\nAllowedIPs = ".$client_ip."/32\nPersistentKeepalive = 60";
        fwrite($file, $append_txt);
        fclose($file);
    }

    private function _genKeyPair(){

        $key_pair = array();

        // get private key

        $fd = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        
        $process = proc_open('tunsafe genkey', $fd, $pipes);
        if(is_resource($process)){
            fclose($pipes[0]);
            $key_pair['prikey'] = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }

        // get public key

        $fd2 = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $process = proc_open('echo '.$key_pair['prikey'].'|tunSafe pubkey', $fd2, $pipes2);
        if(is_resource($process)){
            fclose($pipes2[0]);
            $key_pair['pubkey'] = stream_get_contents($pipes2[1]);
            fclose($pipes2[1]);
        }

        return $key_pair;
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

    private function model_getinfo($sid){
        return $ip_int = $this->db->select('
                ip_allocate.ip AS ip,
                ip_allocate.p6 AS p6
            ')
            ->from('ip_allocate')
            ->where('sid', $sid)
            ->get()
            ->row_array();
    }

    private function model_registerIfNot($sid){

        $not_register = $this->db->select('
                COUNT(*) AS cnt
            ')
            ->from('ip_allocate')
            ->where('sid', $sid)
            ->get()
            ->row_array()['cnt'] == '0';
        
        if($not_register){

            $min_ip_int = ip2long('10.128.0.1');
            $data = array(
                'sid' => $sid,
                'ip' => 0
            );
            $this->db->insert('ip_allocate', $data);
            $db_id = $this->db->insert_id();
            $ip_int = $min_ip_int + $db_id;

            $key_pair = $this->_genKeyPair();
            $this->_appendConfig(long2ip($ip_int), $key_pair['pubkey']);

            $data = array(
                'ip' => $ip_int,
                'p6' => $key_pair['prikey']
            );

            $this->db->where('sid', $sid);
            $this->db->update('ip_allocate', $data);
        }
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
            return false;
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