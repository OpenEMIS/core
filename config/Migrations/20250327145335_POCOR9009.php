<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Text;

class POCOR9009 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();
        $this->updateSecurityFunction();
    }

    public function down()
    {
        $this->restoreTable();
    }

    /**
     * @return void
     */
    public function backupTables()
    {
        if (!$this->hasTable('z_9009_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9009_security_functions` LIKE `security_functions`');
            $this->execute('INSERT INTO `z_9009_security_functions` SELECT * FROM `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * @return void
     */
    public function restoreTable()
    {
        if ($this->hasTable('z_9009_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_9009_security_functions` TO `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * @return void
     */
    public function updateSecurityFunction()
    {
        $this->execute("
            UPDATE security_functions
            SET controller = 'Students',
                _view = 'Counsellings.index|Counsellings.view',
                _edit = 'Counsellings.edit',
                _add = 'Counsellings.add',
                _delete = 'Counsellings.delete'
            WHERE name = 'Counselling' AND module = 'Institutions';
        ");
    }
}
