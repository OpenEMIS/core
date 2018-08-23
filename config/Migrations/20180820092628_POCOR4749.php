<?php

use Phinx\Migration\AbstractMigration;

class POCOR4749 extends AbstractMigration
{
    public function up()
    {

        $sql = "UPDATE `scholarship_financial_assistance_types` 
                SET `code` = 'FULLSCHOLARSHIP', 
                    `name` = 'Full Scholarship' 
                WHERE `name` = 'Scholarship'";

        $this->execute($sql);

        $datas = [
            [
              'code'  => 'PARTIALSCHOLARSHIP',
              'name'  => 'Partial Scholarship'
            ],
            [
              'code'  => 'GRANT',
              'name'  => 'Grant'
            ]
        ];

        $this->table('scholarship_financial_assistance_types')->insert($datas)->save();        
    }

    public function down()
    {
        $this->execute('DELETE FROM scholarship_financial_assistance_types WHERE name in ("Partial Scholarship","Grant")');

        $sql = "UPDATE `scholarship_financial_assistance_types` 
                SET `code` = 'SCHOLARSHIP', 
                    `name` = 'Scholarship' 
                WHERE `name` = 'Full Scholarship'";

        $this->execute($sql);        
    }    
}