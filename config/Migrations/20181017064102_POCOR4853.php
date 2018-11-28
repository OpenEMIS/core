<?php

use Phinx\Migration\AbstractMigration;

class POCOR4853 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4853_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4853_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentProgrammes.index|StudentProgrammes.view' WHERE `id` = 7010");
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentClasses.index|StudentClasses.view' WHERE `id` = 7011");
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentSubjects.index|StudentSubjects.view' WHERE `id` = 7012");
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentAbsences.index|StudentAbsences.view' WHERE `id` = 7013");
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentBehaviours.index|StudentBehaviours.view' WHERE `id` = 7014");
    }

    // rollback
    public function down()
    {
        $this->dropTable('security_functions');
        $this->table('z_4853_security_functions')->rename('security_functions');
    }
}
