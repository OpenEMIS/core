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
                'model' => 'Institution.Salaries',
                'column_name' => 'staff_id',
                'description' => 'OpenEMIS ID',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 0, 
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.Salaries',
                'column_name' => 'salary_date',
                'description' => '( DD/MM/YYYY )',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 0, 
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.Salaries',
                'column_name' => 'gross_salary',
                'description' => NULL,
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.Salaries',
                'column_name' => 'salary_addition_type_id',
                'description' => NULL,
                'order' => 4,
                'is_optional' => 1,
                'foreign_key' => 2,
                'lookup_plugin' => 'Staff',
                'lookup_model' => 'SalaryAdditionTypes',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.Salaries',
                'column_name' => 'amount_addition',
                'description' => NULL,
                'order' => 5,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.Salaries',
                'column_name' => 'salary_deduction_type_id',
                'description' => NULL,
                'order' => 6,
                'is_optional' => 1,
                'foreign_key' => 2,
                'lookup_plugin' => 'Staff',
                'lookup_model' => 'SalaryDeductionTypes',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.Salaries',
                'column_name' => 'amount_deduction',
                'description' => NULL,
                'order' => 7,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.Salaries',
                'column_name' => 'comment',
                'description' => '(Optional)',
                'order' => 8,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ]
        ];

        $this->insert('import_mapping', $data);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_5182_import_mapping` TO `import_mapping`');
    }
}
