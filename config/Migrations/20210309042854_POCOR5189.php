<?php
use Migrations\AbstractMigration;

class POCOR5189 extends AbstractMigration
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
        $this->execute('TRUNCATE TABLE `institution_associations`');
        $this->execute('TRUNCATE TABLE `institution_association_staff`');
        $this->execute('TRUNCATE TABLE `institution_association_student`');
        $this->execute('DROP TABLE zz_5189_security_functions');
        $this->execute('DELETE FROM `security_functions` WHERE `name` = "Associations"');
        // Backup table for security_functions
        $this->execute('CREATE TABLE `zz_5189_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5189_security_functions` SELECT * FROM `security_functions`');

        // Insert Association in security_function table
        $this->execute("INSERT INTO `security_functions` ( `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES ('Associations', 'Institutions', 'Institutions', 'Students - Academic', '2000', 'StudentAssociations.index|StudentAssociations.view', NULL, NULL, NULL, NULL, '409', '1', NULL, '2', '2020-10-26 13:28:18', '1', '2019-10-31 11:05:55')");
        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
            VALUES ('Associations', 'Staff', 'Institutions', 'Staff - Career', '3000', 'StaffAssociations.index|StaffAssociations.view', NULL, NULL, NULL, NULL, '212', '1', NULL, '2', '2020-10-27 13:28:45', '1', '2016-11-27 03:17:43')");
        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
            VALUES ('Associations', 'Institutions', 'Institutions', 'Academic', '8', 'Associations.index|Associations.view', 'Associations.edit', 'Associations.add', 'Associations.remove',  'Associations.excel', '119', '1', NULL, '2', '2020-10-27 13:28:45', '1', '2016-11-27 03:17:43')");
    }

      // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5189_security_functions` TO `security_functions`');
    }
}
