
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Drop_fast_access_table extends CI_Migration{

    public function up(){
        
        if($this->db->table_exists('fast_access')){
            $this->db->query('DROP TABLE fast_access;');
        }  
    }

    public function down(){
        
    }
}