<?php


use Phinx\Migration\AbstractMigration;

class POCOR9493 extends AbstractMigration
{

    public function up(): void
    {
        $this->updateCreateZTable();
        $this->updateMappingFields();

    }

    private function updateCreateZTable(): void
    {
        try {
            $this->execute('CREATE TABLE `z_9493_security_functions` LIKE `security_functions`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_9493_security_functions` SELECT * FROM `security_functions`');
        } catch (\Exception $e) {

        }
    }

    private function updateMappingFields(): void
    {
        $updates = [

            // VIEW permissions
            "UPDATE security_functions
            SET _view = CONCAT(
                IFNULL(_view, ''),
                '|ExternalDataSourceIdentity.index',
                '|ExternalDataSourceIdentity.view',
                '|ExternalAlertServiceSMS.index',
                '|ExternalAlertServiceSMS.view',
                '|ExternalDataSourceExams.index',
                '|ExternalDataSourceExams.view',
                '|ExternalDataSourceLMS.index',
                '|ExternalDataSourceLMS.view'
            )
            WHERE name = 'External Data Source'
              AND category = 'System Configurations'
            ",

            // EDIT permissions
            "UPDATE security_functions
            SET _edit = CONCAT(
                IFNULL(_edit, ''),
                '|ExternalDataSourceIdentity.edit',
                '|ExternalAlertServiceSMS.edit',
                '|ExternalDataSourceExams.edit',
                '|ExternalDataSourceLMS.edit'
            )
            WHERE name = 'External Data Source'
              AND category = 'System Configurations'
            ",
        ];

        foreach ($updates as $query) {
            $this->execute($query);
        }
    }

    public function down(): void
    {
        try {
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_9493_security_functions` TO `security_functions`');
        } catch (\Exception $e) {

        }

    }


}
