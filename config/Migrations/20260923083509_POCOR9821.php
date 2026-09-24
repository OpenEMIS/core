<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9821 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_9821_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_9821_security_functions` SELECT * FROM `security_functions`');

        $this->execute("INSERT INTO `security_functions` (
                                  `id`,
                                  `name`,
                                  `controller`,
                                  `module`,
                                  `category`,
                                  `parent_id`,
                                  `_view`,
                                  `_edit`,
                                  `_add`,
                                  `_delete`,
                                  `_execute`,
                                  `order`,
                                  `visible`,
                                  `description`,
                                  `modified_user_id`,
                                  `modified`,
                                  `created_user_id`,
                                  `created`) VALUES (NULL,
                                                     'Sync', 'Institutions', 'Institutions', 'Students', '1000',
                                                     NULL, NULL,
                                                     NULL, NULL, 'SyncUser.execute',
                                                     '84', '1', NULL, NULL, NULL,
                                                     '2',  '" . date('Y-m-d H:i:s') . "');"  
            );
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_9821_security_functions` TO `security_functions`');
    }
}
