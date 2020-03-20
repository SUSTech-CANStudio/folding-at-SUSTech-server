<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Add_ip_column_in_ip_allocate extends CI_Migration{

    public function up(){
        
        
        $this->db->query('ALTER TABLE `ip_allocate` ADD COLUMN `ip` INT NOT NULL AFTER `sid`;');
        
    }

    public function down(){
        $this->db->query('ALTER TABLE `ip_allocate` DROP COLUMN `ip`;');
    }
}