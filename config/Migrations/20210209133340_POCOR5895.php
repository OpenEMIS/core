<?php
use Migrations\AbstractMigration;

class POCOR5895 extends AbstractMigration
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

        $this->execute('CREATE TABLE `zz_5895_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5895_security_functions` SELECT * FROM `security_functions`');

        $this->execute('CREATE TABLE `z_5895_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5895_locale_contents` SELECT * FROM `locale_contents`');

        // Create tables staff_payslips
       
        //$this->execute("CREATE TABLE `staff_payslips` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(250) NOT NULL,`description` text, `file_name` varchar(250) NOT NULL, `file_content` longblob NOT NULL,`staff_id` int(11) NOT NULL COMMENT 'links to security_users.id', `modified_user_id` int(11) DEFAULT NULL, `modified` datetime DEFAULT NULL, `created_user_id` int(11) NOT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`) )");

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 409');
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 410');

         $data = [
            [ 
                'name' => 'Payslips', 
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Finance',
                'parent_id' => 3000,
                '_view' => 'Payslips.index|Payslips.view',
                '_edit' => 'Payslips.edit',
                '_add' => 'Payslips.add',
                '_delete' => 'Payslips.remove',
                'order' => 410,
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
                  
            ],
            [ 
                'name' => 'Payslips', 
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Staff - Finance',
                'parent_id' => 7000,
                '_view' => 'StaffPayslips.index|StaffPayslips.view',
                'order' => 411,
                'created_user_id' => 1,
                'created' =>date('Y-m-d H:i:s')
                  
            ]
        ];
        
        $this->insert('security_functions', $data);

        $data = [

            [ 
                'en' => 'Payslips', 
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
                  
            ]
        ];
        
        $this->insert('locale_contents', $data);


    }

    public function down()
    {
        // For tables
        //$this->execute('DROP TABLE IF EXISTS `staff_payslips`');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5895_security_functions` TO `security_functions`');
        
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5895_locale_contents` TO `locale_contents`');
        
    }
}
