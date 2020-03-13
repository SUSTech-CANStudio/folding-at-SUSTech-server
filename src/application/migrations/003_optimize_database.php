
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Optimize_database extends CI_Migration{

    public function up(){
        
        if($this->db->table_exists('fast_access')){
            $this->db->query('ALTER TABLE `fast_access` 
            DROP COLUMN `id`,
            ADD INDEX `access_key` (`access_key` ASC),
            DROP PRIMARY KEY;');
        }  
    }

    public function down(){
        if($this->db->table_exists('fast_access')){
            $this->db->query('ALTER TABLE `fast_access` 
            ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT AFTER `timestamp`,
            ADD PRIMARY KEY (`id`);
            ');
            $this->db->query('ALTER TABLE `fast_access` 
            DROP INDEX `access_key` ;');
        }
    }
}