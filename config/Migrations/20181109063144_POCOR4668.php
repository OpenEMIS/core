<?php

use Phinx\Migration\AbstractMigration;

class POCOR4668 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4668_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4668_security_functions` SELECT * FROM `security_functions`');

        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1081');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);
        $data = [
            'id' => 1089,
            'name' => 'Import Competency Results',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 8,
            '_execute' => 'ImportCompetencyResults.add|ImportCompetencyResults.template|ImportCompetencyResults.results|ImportCompetencyResults.downloadFailed|ImportCompetencyResults.downloadPassed',
            'order' => $order,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();    
    }

    public function down()
    { 
        $this->execute('DROP TABLE security_functions');
        $this->table('z_4668_security_functions')->rename('security_functions');
    }
}
