<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Add_p5_in_ip_allocate extends CI_Migration{

    public function up(){
        
        
        $this->db->query('ALTER TABLE `ip_allocate` 
        ADD COLUMN `p5` VARCHAR(512) NOT NULL AFTER `ip`;');
        
    }

    public function down(){
        $this->db->query('ALTER TABLE `ip_allocate` DROP COLUMN `p5`;');
    }
}