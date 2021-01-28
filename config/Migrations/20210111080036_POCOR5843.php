<?php
use Migrations\AbstractMigration;

class POCOR5843 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5843_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5843_security_functions` SELECT * FROM `security_functions`');
        // End
        // 1091 = Import Student Guardians
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1091');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` > ' . $order);

        $data = [
            //'id' => ,
            'name' => 'Import Extracurriculars',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 1012,
            '_execute' => 'ImportStudentExtracurriculars.add|ImportStudentExtracurriculars.template|ImportStudentExtracurriculars.results|ImportStudentExtracurriculars.downloadFailed|ImportStudentExtracurriculars.downloadPassed',
            'order' => $order+1,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();
    }

    //  rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5843_security_functions` TO `security_functions`');
    }
}
