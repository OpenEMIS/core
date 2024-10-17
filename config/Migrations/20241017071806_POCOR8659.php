<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8659 extends AbstractMigration
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
        // Check if a row with 'colour' value exists in the 'name' column
        $row = $this->fetchRow("SELECT * FROM themes WHERE name = 'Colour'");

        if (!$row) {
            //backup
            $this->execute('CREATE TABLE `z_8659_themes` LIKE `themes`');
            $this->execute('INSERT INTO `z_8659_themes` SELECT * FROM `themes`');

            $this->execute("INSERT INTO `themes` (`id`, `name`, `value`, `content`, `default_value`, `default_content`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Colour', NULL, NULL, '6699CC', NULL, NULL, CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP)");
        }
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `themes`');
        $this->execute('RENAME TABLE `z_8659_themes` TO `themes`');
    }
}
