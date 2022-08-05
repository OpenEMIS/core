<?php
use Migrations\AbstractMigration;

class POCOR6894 extends AbstractMigration
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
        // create backup for transfer_connections     
        $this->execute('CREATE TABLE `z_6894_transfer_connections` LIKE `transfer_connections`');
        $this->execute('INSERT INTO `z_6894_transfer_connections` SELECT * FROM `transfer_connections`');

        $this->execute('ALTER TABLE `transfer_connections` CHANGE `password` `password` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `transfer_connections`');
        $this->execute('RENAME TABLE `z_6894_transfer_connections` TO `transfer_connections`');
    }
}
