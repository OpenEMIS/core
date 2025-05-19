<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9122 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_9122_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_9122_labels` SELECT * FROM `labels`');
        $this->execute("UPDATE `labels` SET `code` = 'Score' ,`name` = 'Score' WHERE `labels`.`module` = 'SpecialNeeds' 
        and  `labels`.`field` = 'special_need_difficulty_id'");
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_9122_labels` TO `labels`');
    }
}
