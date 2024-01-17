<?php
use Migrations\AbstractMigration;

class POCOR8058 extends AbstractMigration
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
        // Backup Table
        $this->execute('CREATE TABLE `zz_8058_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_8058_security_functions` SELECT * FROM `security_functions`');
        
        //Insert security functions for User Group List
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Manuals', 'Manuals', 'Administration', 'Manuals', '5000', 'Manuals.index|Manuals.view', 'Manuals.edit', '', '', NULL, '276', '1', NULL, '1', NULL, '1', '2024-01-16 02:41:00');");      
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_8058_security_functions` TO `security_functions`');
    }
}
