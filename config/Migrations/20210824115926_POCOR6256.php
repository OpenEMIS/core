<?php
use Migrations\AbstractMigration;

class POCOR6256 extends AbstractMigration
{
    public function up()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6256_security_functions`');
        $this->execute('CREATE TABLE `zz_6256_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6256_security_functions` SELECT * FROM `security_functions`');

        $this->insert('security_functions', [
            'name' => 'Processes',
            'controller' => 'ReportCards',
            'module' => 'Administration',
            'category' => 'Report Cards',
            'parent_id' => 5000,
            '_view' => 'Processes.index|Processes.view',
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => 'Processes.remove',
            '_execute' => NULL,
            'order' => 313,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
        /** END: security_functions table changes */


        /** START: report_card_processes table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6256_report_card_processes`');
        $this->execute('CREATE TABLE `zz_6256_report_card_processes` LIKE `report_card_processes`');
        $this->execute('INSERT INTO `zz_6256_report_card_processes` SELECT * FROM `report_card_processes`');
        $this->execute('ALTER TABLE `report_card_processes` DROP PRIMARY KEY, ADD PRIMARY KEY (`report_card_id`) USING BTREE');
        /** END: report_card_processes table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6256_security_functions` TO `security_functions`');
        /** END: security_functions table changes */


        /** START: report_card_processes table changes */
        $this->execute('DROP TABLE IF EXISTS `report_card_processes`');
        $this->execute('RENAME TABLE `zz_6256_report_card_processes` TO `report_card_processes`');
        /** END: report_card_processes table changes */
    }
}
