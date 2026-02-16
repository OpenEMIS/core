<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8671 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8671_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_8671_config_items` SELECT * FROM `config_items`');
        $this->execute("UPDATE `config_items` SET `visible` = 0 WHERE `type` = 'Columns for Institutions Classes List Page'");
        $this->execute("UPDATE `config_items` SET `visible` = 0 WHERE `type` = 'Fields for Institutions Classes Details Page'");          
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `z_8671_config_items` TO `config_items`');
    }
}
