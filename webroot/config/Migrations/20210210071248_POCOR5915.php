<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR5915 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5915_backup_of_drop_table_staff_salary_additions` LIKE `staff_salary_additions`');
        $this->execute('INSERT INTO `zz_5915_backup_of_drop_table_staff_salary_additions` SELECT * FROM `staff_salary_additions`');

        $this->execute('CREATE TABLE `zz_5915_backup_of_drop_table_staff_salary_deductions` LIKE `staff_salary_deductions`');
        $this->execute('INSERT INTO `zz_5915_backup_of_drop_table_staff_salary_deductions` SELECT * FROM `staff_salary_deductions`');

        $StaffSalaryAdditions     = TableRegistry::get('Staff.SalaryAdditions');
        $StaffSalaryDeductions    = TableRegistry::get('Staff.SalaryDeductions');

        $data = $StaffSalaryAdditions->find()
                                    ->select([
                                        $StaffSalaryAdditions->aliasField('amount'),
                                        $StaffSalaryAdditions->aliasField('salary_addition_type_id'),
                                        $StaffSalaryAdditions->aliasField('staff_salary_id'),
                                        $StaffSalaryDeductions->aliasField('amount'),
                                        $StaffSalaryDeductions->aliasField('salary_deduction_type_id'),
                                        $StaffSalaryDeductions->aliasField('staff_salary_id')
                                    ])
                                    ->innerJoin([$StaffSalaryDeductions->alias() => $StaffSalaryDeductions->table()], [
                                            $StaffSalaryAdditions->aliasField('staff_salary_id = ') . $StaffSalaryDeductions->aliasField('staff_salary_id'),
                                    ])
                                    ->toArray();
        if (!empty($data)) {
           foreach ($data as $key => $value) {
                $addAmount     = $value->amount;
                $additionType  = $value->salary_addition_type_id;
                $staffSalaryId = $value->staff_salary_id;
                $deductAmount  = $value->SalaryDeductions['amount'];
                $deductionType = $value->SalaryDeductions['salary_deduction_type_id'];

            $record = [
                    [
                        'amount' => $addAmount ,
                        'salary_addition_type_id' =>  $additionType ,
                        'salary_deduction_type_id' => 0,
                        'staff_salary_id' => $staffSalaryId,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ],
                    [
                        'amount' => $deductAmount ,
                        'salary_addition_type_id' =>  0 ,
                        'salary_deduction_type_id' => $deductionType,
                        'staff_salary_id' => $staffSalaryId,
                        'created_user_id' => 1,
                        'created' => date('Y-m-d H:i:s')
                    ]
                ];

                $table = $this->table('staff_salary_transactions');
                $table->insert($record);
                $table->saveData();
            }
        }

        $this->execute('DROP TABLE IF EXISTS `staff_salary_additions`');
        $this->execute('DROP TABLE IF EXISTS `staff_salary_deductions`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_salary_additions`');
        $this->execute('RENAME TABLE `zz_5915_backup_of_drop_table_staff_salary_additions` TO `staff_salary_additions`');
        $this->execute('DROP TABLE IF EXISTS `staff_salary_deductions`');
        $this->execute('RENAME TABLE `zz_5915_backup_of_drop_table_staff_salary_deductions` TO `staff_salary_deductions`');
    }
}
