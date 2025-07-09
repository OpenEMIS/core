<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Cake\Utility\Inflector;

class POCOR9042 extends AbstractMigration
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
        //back up for `assessment_item_student_exemptions` table
        $this->execute('CREATE TABLE `z_9042_assessment_item_student_exemptions` LIKE `assessment_item_student_exemptions`');
        $this->execute('INSERT INTO `z_9042_assessment_item_student_exemptions` SELECT * FROM `assessment_item_student_exemptions`');

        //Alter for `assessment_item_student_exemptions` table added type column
        $this->execute("ALTER TABLE `assessment_item_student_exemptions` ADD `type` INT(10) NULL DEFAULT NULL AFTER `assessment_period_id`;"); 
    }

    public function down(): void
    {
        //restore for `assessment_item_student_exemptions` table
        $this->execute('DROP TABLE IF EXISTS `assessment_item_student_exemptions`');
        $this->execute('RENAME TABLE `z_9042_assessment_item_student_exemptions` TO `assessment_item_student_exemptions`');
    }
}
