<?php

use Phinx\Migration\AbstractMigration;

class POCOR5034 extends AbstractMigration
{
    // commit
    public function up()
    {
        $this->execute('CREATE TABLE `z_5034_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_5034_import_mapping` SELECT * FROM `import_mapping`');

        $this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Outcome.OutcomeTemplates','code','','1','0','0',NULL,NULL,NULL)");
		$this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Outcome.OutcomeTemplates','name','','2','0','0',NULL,NULL,NULL)");
		$this->execute("INSERT into `import_mapping` (`model`, `column_name`, `description`, `order`, `is_optional`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) values('Outcome.OutcomeTemplates','description','','3','0','0',NULL,NULL,NULL)");
        ## Update permission
        $this->execute('CREATE TABLE `zz_5034_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5034_security_functions` SELECT * FROM `security_functions`');
        $this->execute("UPDATE `security_functions` SET `_execute` = 'ImportOutcomeTemplates.add|ImportOutcomeTemplates.template|ImportOutcomeTemplates.results|ImportOutcomeTemplates.downloadFailed|ImportOutcomeTemplates.downloadPassed' WHERE `name` = 'Outcome Setup' AND `controller` = 'Outcomes' AND `module` = 'Administration' AND `category` = 'Learning Outcomes'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_5034_import_mapping` TO `import_mapping`');

        $this->execute('RENAME TABLE `zz_5034_security_functions` TO `security_functions`');
    }
}
