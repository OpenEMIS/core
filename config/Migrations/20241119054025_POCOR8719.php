<?php
declare(strict_types=1);

use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR8719 extends AbstractMigration
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
        //Backup `institution_student_absence_details` table 
        $this->execute('CREATE TABLE `z_8719_institution_student_absence_details` LIKE `institution_student_absence_details`');
        $this->execute('INSERT INTO `z_8719_institution_student_absence_details` SELECT * FROM `institution_student_absence_details`');

        $this->execute('ALTER TABLE `institution_student_absence_details` DROP FOREIGN KEY `insti_stude_absen_detai_fk_stude_absen_reaso_id`'); 
    }

    public function down() {
        //Restore institution_student_absence_details table 
        $this->execute('DROP TABLE IF EXISTS `institution_student_absence_details`');
        $this->execute('RENAME TABLE `z_8719_institution_student_absence_details` TO `institution_student_absence_details`');
    }
}
