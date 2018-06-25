<?php

use Phinx\Migration\AbstractMigration;

class POCOR4619 extends AbstractMigration
{
    public function up()
    {
        // create backup for security_functions     
        $this->execute('CREATE TABLE `z_4619_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4619_security_functions` SELECT * FROM `security_functions`');

        // Gets the current order for MAP
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 6008');
        $order = $row['order'];

        //Updates all the order by +1
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` >= ' . $order);

        //Insert workflow into it
        $this->insert('security_functions', [
            'id' => 6013,
            'name' => 'Workflows',
            'controller' => 'Reports',
            'module' => 'Reports',
            'category' => 'Reports',
            'parent_id' => -1,
            '_view' => 'Workflows.index',
            '_add' => 'Workflows.add',
            '_execute' => 'Workflows.download',
            'order' => $order,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4619_security_functions` TO `security_functions`');
    }
}
