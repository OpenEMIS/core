<?php
use Migrations\AbstractMigration;

class POCOR5454 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5454_system_authentications` LIKE `system_authentications`');
        $this->execute('INSERT INTO `z_5454_system_authentications` SELECT * FROM `system_authentications`');
        // End	
		
        $this->execute('ALTER TABLE system_authentications MODIFY COLUMN mapped_username VARCHAR(100)');
        $this->execute('ALTER TABLE system_authentications MODIFY COLUMN mapped_first_name VARCHAR(100)');
        $this->execute('ALTER TABLE system_authentications MODIFY COLUMN mapped_last_name VARCHAR(100)');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `system_authentications`');
        $this->execute('RENAME TABLE `z_5454_system_authentications` TO `system_authentications`');
    }
}
