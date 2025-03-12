<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8864 extends AbstractMigration
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

        $this->execute('CREATE TABLE `z_8864_appraisal_criterias` LIKE `appraisal_criterias`');
        $this->execute('INSERT INTO `z_8864_appraisal_criterias` SELECT * FROM `appraisal_criterias`');
        $this->execute("ALTER TABLE `appraisal_criterias` 
                ADD `description` VARCHAR(255) DEFAULT NULL 
                AFTER `name`");
    }

    public function down()
    {

        $this->execute('DROP TABLE IF EXISTS `appraisal_criterias`');
        $this->execute('RENAME TABLE `z_8864_appraisal_criterias` TO `appraisal_criterias`');
        $this->execute('ALTER TABLE `appraisal_criterias` DROP COLUMN IF EXISTS `description`');
    }
}
