<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8331 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8331_feeders_institutions` LIKE `feeders_institutions`');
        $this->execute('INSERT INTO `z_8331_feeders_institutions` SELECT * FROM `feeders_institutions`');
        $this->execute('ALTER TABLE `feeders_institutions` DROP PRIMARY KEY');
        $this->execute('ALTER TABLE `feeders_institutions` ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)');

    }

    // rollback
    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `feeders_institutions`');
        $this->execute('RENAME TABLE `z_8331_feeders_institutions` TO `feeders_institutions`');
        
    }
}
