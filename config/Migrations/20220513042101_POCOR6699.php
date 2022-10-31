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
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentAssessments.index' WHERE  `security_functions`.`category` = 'Students - Academic' AND `security_functions`.`name` = 'Assessments' AND `security_functions`.`controller` = 'Profiles' AND `security_functions`.`module` = 'Personal'  AND `security_functions`.`_view` = 'StudentResults.index.view'");
        //END security_functions..
    }
    
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6699_security_functions` TO `security_functions`');
    }
}
