<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8147 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE `zz_8147_field_options` LIKE `field_options`');
        $this->execute('INSERT INTO `zz_8147_field_options` SELECT * FROM `field_options`');
        $this->execute("UPDATE `field_options` SET `field_options`.`category` = 'Staff', `field_options`.`name` = 'Staff Behaviour Classifications' WHERE `field_options`.`name` = 'Behaviour Classifications' AND `field_options`.`table_name` = 'behaviour_classifications'");
   
    }
    
    //For Rollback 
    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `field_options`');
        $this->execute('RENAME TABLE `zz_8147_field_options` TO `field_options`');
    }

}
