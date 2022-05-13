<?php
use Migrations\AbstractMigration;

class POCOR6699 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6699_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6699_security_functions` SELECT * FROM `security_functions`');
        $this->execute("INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Assessments', 'Profiles', 'Personal', 'Students - Academic', '9030', 'StudentAssessments.index', NULL, NULL, NULL, NULL, '466', '1', NULL, NULL, NULL, '1', NOW())");
        //END security_functions..
    }
    
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6699_security_functions` TO `security_functions`');
    }
}
