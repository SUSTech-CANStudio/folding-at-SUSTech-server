<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Ai_in_access_table extends CI_Migration{

    public function up(){
        
        if($this->db->table_exists('fast_access')){
            $this->db->query('ALTER TABLE `fast_access` 
                CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT ;');
        }  
    }

    public function down(){
        if($this->db->table_exists('fast_access')){
            $this->db->query('ALTER TABLE `fast_access` 
            CHANGE COLUMN `id` `id` INT(11) NOT NULL ;');
        }
    }
}
?>