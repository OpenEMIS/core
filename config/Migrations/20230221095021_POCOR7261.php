<?php
use Migrations\AbstractMigration;

class POCOR7261 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {

        // Backup table
        $this->execute('CREATE TABLE `zz_7261_api_credentials` LIKE `api_credentials`');
        $this->execute('INSERT INTO `zz_7261_api_credentials` SELECT * FROM `api_credentials`');
        $this->execute('ALTER TABLE `api_credentials` ADD `api_key` VARCHAR(200) NULL AFTER `public_key`');
    
    }
         
    // rollback
    public function down()
    {
        // Drop summary tables
        $this->execute('DROP TABLE IF EXISTS `api_credentials`');  
        $this->execute('RENAME TABLE `zz_7261_api_credentials` TO `api_credentials`'); 
    }
}
