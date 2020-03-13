<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Add_access_table extends CI_Migration{

    public function up(){
        
        if( ! $this->db->table_exists('fast_access')){
            $this->db->query(
                'CREATE TABLE `fast_access` (
                `id` INT NOT NULL,
                `access_key` VARCHAR(256) NOT NULL,
                `timestamp` DATETIME NOT NULL,
                PRIMARY KEY (`id`));');
        }  
    }

    public function down(){
        if($this->db->table_exists('fast_access')){
            $this->db->query('DROP TABLE fast_access');
        }
    }
}
?>