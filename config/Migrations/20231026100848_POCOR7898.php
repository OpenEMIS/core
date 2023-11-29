<?php
use Migrations\AbstractMigration;

class POCOR7898 extends AbstractMigration
{
    public function up()
    {
        
        $this->execute('CREATE TABLE `zz_7898_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7898_security_functions` SELECT * FROM `security_functions`');
        $this->execute("UPDATE `security_functions` SET `_execute` = 'Assessments.excel|resultsExport|reportCardGenerate.add|reportCardGenerate' WHERE `security_functions`.`name` = 'Assessments' AND `security_functions`.`controller` = 'Institutions' AND `security_functions`.`module` = 'Institutions'  AND `security_functions`.`category` = 'Students'");

    }
    public function down()
    {
     
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7898_security_functions` TO `security_functions`');
    }
}
