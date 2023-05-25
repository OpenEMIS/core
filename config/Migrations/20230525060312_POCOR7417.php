<?php
use Migrations\AbstractMigration;

class POCOR7417 extends AbstractMigration
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
        //Backup import_mapping table
        $this->execute('CREATE TABLE `zz_7417_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_7417_import_mapping` SELECT * FROM `import_mapping`');
        //import_mapping
        $data = [
            [
                'model' => 'Institution.InstitutionPositions',
                'column_name' => 'shift_id',
                'description' => '',
                'order' => 6,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Institution',
                'lookup_model' => 'InstitutionShifts',
                'lookup_column' => 'id'
            ]
        ];
        $this->insert('import_mapping', $data);
    }
    public function down()
    {
        // Field Options
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_7417_import_mapping` TO `import_mapping`');
    }
}
