<?php

use Phinx\Migration\AbstractMigration;

class POCOR4426 extends AbstractMigration
{
    public function up()
    {
        // Credentials permission
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 5077');
        $order = $row['order'];

        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);

        $this->execute("UPDATE `security_functions` SET `category` = 'APIs' WHERE `id` = 5077");

        $this->insert('security_functions', [
            'id' => 5089,
            'name' => 'Securities',
            'controller' => 'ApiSecurities',
            'module' => 'Administration',
            'category' => 'APIs',
            'parent_id' => 5000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => null,
            '_delete' => null,
            '_execute' => null,
            'order' => $order,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        // Credentials permission
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 5089');
        $order = $row['order'];

        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= ' . $order);

        $this->execute("UPDATE `security_functions` SET `category` = 'System Configurations' WHERE `id` = 5077");

        $this->execute("DELETE FROM `security_functions` WHERE `id` = 5089");
    }
}
