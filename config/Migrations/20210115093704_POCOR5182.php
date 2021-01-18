<?php
use Migrations\AbstractMigration;

class POCOR5182 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_5182_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_5182_import_mapping` SELECT * FROM `import_mapping`');

        //import_mapping
        $data = [
            [
                'model' => 'Institution.StaffSalaries',
                'column_name' => 'salary_date',
                'description' => '( DD/MM/YYYY )',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 0, 
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.StaffSalaries',
                'column_name' => 'comment',
                'description' => '(Optional)',
                'order' => 2,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.StaffSalaries',
                'column_name' => 'gross_salary',
                'description' => ' ',
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 1,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.StaffSalaries',
                'column_name' => 'additions',
                'description' => NULL,
                'order' => 4,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.StaffSalaries',
                'column_name' => 'deductions',
                'description' => NULL,
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.StaffSalaries',
                'column_name' => 'net_salary',
                'description' => NULL,
                'order' => 6,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.StaffSalaries',
                'column_name' => 'staff_id',
                'description' => NULL,
                'order' => 7,
                'is_optional' => 0,
                'foreign_key' => 1,
                'lookup_plugin' => 'User',
                'lookup_model' => 'Users',
                'lookup_column' => 'id'
            ]
        ];

        $this->insert('import_mapping', $data);
        //import_mapping

        //backup
        $this->execute('CREATE TABLE `z_5182_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5182_security_functions` SELECT * FROM `security_functions`');


        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` >= 105');
        $row = [
            [
                'name' => 'Import Staff Salaries',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Staff',
                'parent_id' => '1016',
                '_view' => NULL, 
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'ImportStaffSalaries.add|ImportStaffSalaries.template|ImportStaffSalaries.results|ImportStaffSalaries.downloadFailed|ImportStaffSalaries.downloadPassed', 
                'order' => 106,
                'visible' => 1,
                'created_user_id' => 2,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('security_functions', $row);
    }

    public function down()
    {
        $this->execute("DELETE FROM import_mapping WHERE model = 'Institution.StaffSalaries' AND category = 'Staff'");

        $this->execute("DELETE FROM `security_functions` WHERE `name` = 'Import Staff Salaries'");

        $this->execute("UPDATE security_functions SET `order` = `order` - 1 WHERE `order` >= 105");
    }
}
