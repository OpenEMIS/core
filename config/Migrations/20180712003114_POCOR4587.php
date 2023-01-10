<?php

use Phinx\Migration\AbstractMigration;

class POCOR4587 extends AbstractMigration
{
    public function up()
    {
        $data = [
                'en' => 'Potential Wrong Birthdates',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
        ];

        $table = $this->table('locale_contents');
        $table->insert($data);
        $table->saveData(); 
    }  

    public function down()
    { 
        $this->execute('DELETE FROM locale_contents WHERE en ="Potential Wrong Birthdates"');  
    }
}

