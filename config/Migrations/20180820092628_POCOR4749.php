<?php

use Phinx\Migration\AbstractMigration;

class POCOR4749 extends AbstractMigration
{
    public function up()
    {
        $this->table('scholarship_financial_assistance_types')->rename('z_4749_scholarship_financial_assistance_types');
        $this->execute('CREATE TABLE `scholarship_financial_assistance_types` LIKE `z_4749_scholarship_financial_assistance_types`');
        $this->table('scholarships')->rename('z_4749_scholarships');
        $this->execute('CREATE TABLE `scholarships` LIKE `z_4749_scholarships`');
        $this->execute('INSERT INTO `scholarships` SELECT * FROM `z_4749_scholarships`');

        $data = [
            [
              'id'  => 1,
              'code'  => 'FULLSCHOLARSHIP',
              'name'  => 'Full Scholarship'
            ],
            [
              'id'  => 2,
              'code'  => 'PARTIALSCHOLARSHIP',
              'name'  => 'Partial Scholarship'
            ],
            [
              'id'  => 3,   
              'code'  => 'GRANT',
              'name'  => 'Grant'
            ],
            [
              'id'  => 4,
              'code'  => 'LOAN',
              'name'  => 'Loan'
            ],
            [
              'id'  => 5,
              'code'  => 'DISTANCELEARNING',
              'name'  => 'Distance Learning'
            ]
        ];

        $this->table('scholarship_financial_assistance_types')->insert($data)->save(); 

        $sql = "UPDATE `scholarships` SET `scholarship_financial_assistance_type_id`=4 WHERE scholarship_financial_assistance_type_id =2";

        $this->execute($sql);
    }

    public function down()
    {
        $this->dropTable('scholarship_financial_assistance_types');
        $this->table('z_4749_scholarship_financial_assistance_types')->rename('scholarship_financial_assistance_types');
        $this->dropTable('scholarships');
        $this->table('z_4749_scholarships')->rename('scholarships');
    }
}