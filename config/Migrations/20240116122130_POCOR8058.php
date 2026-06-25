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
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Institution', 'Manuals', 'Administration', 'Manuals', '5000', 'Institution.index|Institution.view', 'Institutions.edit', NULL, NULL, NULL, '284', '1', NULL, NULL, NULL, '2', '2024-01-30 00:01:04');");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Directory', 'Manuals', 'Administration', 'Manuals', '5000', 'Directory.index|Directory.view', 'Directory.edit', NULL, NULL, NULL, '285', '1', NULL, NULL, NULL, '2', '2024-01-30 00:01:04');");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Reports', 'Manuals', 'Administration', 'Manuals', '5000', 'Reports.index|Reports.view', 'Directory.edit', NULL, NULL, NULL, '286', '1', NULL, NULL, NULL, '2', '2024-01-30 00:01:04');");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Administration', 'Manuals', 'Administration', 'Manuals', '5000', 'Administration.index|Administration.view', 'Administration.edit', NULL, NULL, NULL, '287', '1', NULL, NULL, NULL, '2', '2024-01-30 00:01:04');");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Personal', 'Manuals', 'Administration', 'Manuals', '5000', 'Personal.index|Personal.view', 'Personal.edit', NULL, NULL, NULL, '288', '1', NULL, NULL, NULL, '2', '2024-01-30 00:01:04');");
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Guardian', 'Manuals', 'Administration', 'Manuals', '5000', 'Guardian.index|Guardian.view', 'Guardian.edit', NULL, NULL, NULL, '289', '1', NULL, NULL, NULL, '2', '2024-01-30 00:01:04');");
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_8058_security_functions` TO `security_functions`');
    }
}
