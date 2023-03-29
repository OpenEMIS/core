<?php
use Migrations\AbstractMigration;

class POCOR7323 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7323_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7323_security_functions` SELECT * FROM `security_functions`');
        
        //Update security functions for Absence delete permission
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'User Group List', 'Securities', 'Administration', 'Security', '5000', 'UserGroupsList.index|UserGroupsList.view', 'UserGroupsList.edit', 'UserGroupsList.add', 'UserGroupsList.delete', NULL, '2', '1', NULL, NULL, NULL, '2', '2023-03-27 17:42:05');");      
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7323_security_functions` TO `security_functions`');
    }
}
