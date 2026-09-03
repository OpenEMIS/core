<?php

use Phinx\Migration\AbstractMigration;

class POCOR7692 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `zz_7692_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_7692_import_mapping` SELECT * FROM `import_mapping`');
        // import_mapping - columns for the "Import Houses" template
        $data = [
            [
                'model' => 'Institution.InstitutionAssociations',
                'column_name' => 'academic_period_id',
                'description' => 'Code',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'AcademicPeriod',
                'lookup_model' => 'AcademicPeriods',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Institution.InstitutionAssociations',
                'column_name' => 'name',
                'description' => '',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
        ];

        $this->table('import_mapping')->insert($data)->save();
        $this->execute('CREATE TABLE `zz_7692_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7692_security_functions` SELECT * FROM `security_functions`');
        // security_functions - place "Import Houses" right after "Houses"
        $row = $this->fetchRow("SELECT `order` FROM `security_functions` WHERE `name` = 'Houses' AND `controller` = 'Institutions' AND `parent_id` = 8");
        if (empty($row)) {
            throw new \RuntimeException("POCOR7692 migration: could not find the 'Houses' security_functions row (controller=Institutions, parent_id=8) to place 'Import Houses' after.");
        }
        $order = $row['order'] + 1;

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `parent_id` = 8 AND `order` >= ' . $order);

        $securityData = [
            [
                'name' => 'Import Houses',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Academic',
                'parent_id' => 8,
                '_view' => null,
                '_edit' => null,
                '_add' => null,
                '_delete' => null,
                '_execute' => 'ImportHouses.add|ImportHouses.template|ImportHouses.results|ImportHouses.downloadFailed|ImportHouses.downloadPassed',
                'order' => $order,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('security_functions')->insert($securityData)->save();
    }

    public function down()
    {
        // import_mapping
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'Institution.InstitutionAssociations'");

        // security_functions
        $row = $this->fetchRow("SELECT `id`, `order` FROM `security_functions` WHERE `name` = 'Import Houses' AND `controller` = 'Institutions' AND `parent_id` = 8");

        if (!empty($row)) {
            $this->execute('DELETE FROM `security_functions` WHERE `id` = ' . $row['id']);
            $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `parent_id` = 8 AND `order` >= ' . $row['order']);
        }

        $this->execute('DROP TABLE IF EXISTS `zz_7692_import_mapping`');
        $this->execute('DROP TABLE IF EXISTS `zz_7692_security_functions`');
    }
}
