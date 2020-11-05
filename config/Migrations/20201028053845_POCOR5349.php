<?php
use Migrations\AbstractMigration;

class POCOR5349 extends AbstractMigration
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
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 63');
    
        $this->insert('security_functions', [
            'name' => 'Duties',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Staff',
            'parent_id' => 1000,
            '_view' => 'index|view',
            '_add' => 'add',
            '_execute' => '',
            'order' => 64,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $localeContent = [
            [
                'en' => 'Duties',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Appointments',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Duty Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Duty Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('locale_contents', $localeContent);
    }   

    // rollback
    public function down()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 63');
        $this->execute('DELETE FROM security_functions WHERE name = "Duties"');
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Duties'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Appointments'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Duty Type'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Duty Date'");
    }
}
