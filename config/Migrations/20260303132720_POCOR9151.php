<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9151 extends AbstractMigration
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
        // Backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9151_staff_training_needs`');
        $this->execute('CREATE TABLE `zz_9151_staff_training_needs` LIKE `staff_training_needs`');
        $this->execute('INSERT INTO `zz_9151_staff_training_needs` SELECT * FROM `staff_training_needs`');
        // Alter table `staff_training_needs`
        $this->execute("ALTER TABLE `staff_training_needs` CHANGE `training_need_category_id` `training_need_category_id` INT(11) NULL DEFAULT NULL COMMENT 'links to training_need_categories.id'");    
        $this->execute("ALTER TABLE `staff_training_needs` CHANGE `training_need_competency_id` `training_need_competency_id` INT(11) NULL DEFAULT NULL COMMENT 'links to training_need_competencies.id', CHANGE `training_need_sub_standard_id` `training_need_sub_standard_id` INT(11) NULL DEFAULT NULL COMMENT 'links to training_need_sub_standards.id'");    
    }

    public function down(): void
    {
        // Restore from backup
        $this->execute('DROP TABLE IF EXISTS `staff_training_needs`');
        $this->execute('RENAME TABLE `zz_9151_staff_training_needs` TO `staff_training_needs`');
    }
}
