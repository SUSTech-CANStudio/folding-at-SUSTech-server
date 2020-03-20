
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Add_ip_allocate_table extends CI_Migration{

    public function up(){
        
        if( ! $this->db->table_exists('ip_allocate')){
            $this->db->query('CREATE TABLE `ip_allocate` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `sid` VARCHAR(45) NOT NULL,
                PRIMARY KEY (`id`));');
        }  
    }

    public function down(){
        if($this->db->table_exists('ip_allocate')){
            $this->db->query('DROP TABLE ip_allocate;');
        }
    }
}