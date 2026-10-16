<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9835 extends AbstractMigration
{
    /**
     * Adds the Reports > Education permission
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_9835_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_9835_security_functions` SELECT * FROM `security_functions`');

        $row = $this->fetchRow("SELECT MAX(`order`) AS max_order FROM `security_functions` WHERE `module` = 'Reports'");
        $order = $row['max_order'] + 1;

        $this->table('security_functions')->insert([
            'name' => 'Education',
            'controller' => 'Reports',
            'module' => 'Reports',
            'category' => 'Reports',
            'parent_id' => -1,
            '_view' => 'Education.index',
            '_edit' => null,
            '_add' => 'Education.add',
            '_delete' => 'Education.delete',
            '_execute' => 'Education.download',
            'order' => $order,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ])->save();
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_9835_security_functions` TO `security_functions`');
    }
}
