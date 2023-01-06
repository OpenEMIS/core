<?php
use Migrations\AbstractMigration;

class POCOR5376 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `zz_5376_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5376_security_functions` SELECT * FROM `security_functions`');

        $this->execute('CREATE TABLE `zz_5376_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `zz_5376_security_roles` SELECT * FROM `security_roles`');
        // End

        $roles = $this->fetchAll('SELECT * FROM `security_roles`'); 
        if(!empty($roles)){
            foreach ($roles as $key => $value) {
                if($value['name'] == 'Group Administrator'){
                    $code = 'GROUP_ADMINISTRATOR';  
                }elseif ($value['name'] == 'Administrator') {
                    $code = 'ADMINISTRATOR';  
                }elseif ($value['name'] == 'District Officer') {
                    $code = 'DISTRICT_OFFICER';
                }elseif ($value['name'] == 'Principal') {
                    $code = 'PRINCIPAL'; 
                }elseif ($value['name'] == 'Homeroom Teacher') {
                    $code = 'HOMEROOM_TEACHER';
                }elseif ($value['name'] == 'Teacher') {
                    $code = 'TEACHER';
                }elseif ($value['name'] == 'Staff') {
                    $code = 'STAFF';
                }elseif ($value['name'] == 'Student') {
                    $code = 'STUDENT';
                }elseif ($value['name'] == 'Guardian') {
                    $code = 'GUARDIAN';
                }elseif ($value['name'] == 'Superrole') {
                    $code = 'SUPERROLE';
                }elseif ($value['name'] == 'Deputy Principal') {
                    $code = 'DEPUTY_PRINCIPAL';
                }

                $id = $value['id'] ; 
                $this->execute('UPDATE `security_roles` SET `code` = "'. $code .'" WHERE `id` = "'. $id .'" ');
            }
        }      


        // 1021 = Students
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1021');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > ' . $order);

        $data = [
            //'id' => ,
            'name' => 'Budget',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Finance',
            'parent_id' => 8,
            '_view' => 'Budget.index|Budget.view',
            '_edit' => 'Budget.edit',
            '_add' => 'Budget.add',
            '_delete' => 'Budget.remove',
            'order' => $order+1,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();


        $row_ex = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `name` = "Budget"');
        $order_ex = $row_ex['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > ' . $order_ex);

        $data_ex = [
            //'id' => ,
            'name' => 'Expenditure',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Finance',
            'parent_id' => 8,
            '_view' => 'Expenditure.index|Expenditure.view',
            '_edit' => 'Expenditure.edit',
            '_add' => 'Expenditure.add',
            '_delete' => 'Expenditure.remove',
            'order' => $order_ex+1,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('security_functions');
        $table->insert($data_ex);
        $table->saveData();


        $row_in = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `name` = "Expenditure"');
        $order_in = $row_in['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > ' . $order_in);

        $data_in = [
            //'id' => ,
            'name' => 'Income',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Finance',
            'parent_id' => 8,
            '_view' => 'Income.index|Income.view',
            '_edit' => 'Income.edit',
            '_add' => 'Income.add',
            '_delete' => 'Income.remove',
            'order' => $order_in+1,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('security_functions');
        $table->insert($data_in);
        $table->saveData();

    }

    //  rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5376_security_functions` TO `security_functions`');

        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `zz_5376_security_roles` TO `security_roles`');
    }
}
