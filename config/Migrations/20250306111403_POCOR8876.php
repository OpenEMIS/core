<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8876 extends AbstractMigration
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
        // Backup outcome_criterias table
        $this->execute('CREATE TABLE `z_8876_outcome_criterias` LIKE `outcome_criterias`');
        $this->execute('INSERT INTO `z_8876_outcome_criterias` SELECT * FROM `outcome_criterias`');

        $this->execute("ALTER TABLE `outcome_criterias` CHANGE `code` `code` TEXT NULL");
    }

    public function down()
    {
        $this->execute("ALTER TABLE `outcome_criterias` CHANGE `code` `code` VARCHAR(20) NOT NULL");
        $this->execute('DROP TABLE IF EXISTS `outcome_criterias`');
        $this->execute('RENAME TABLE `z_8876_outcome_criterias` TO `outcome_criterias`');
    }
}
