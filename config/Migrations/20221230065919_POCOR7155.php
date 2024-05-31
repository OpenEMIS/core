<?php
use Migrations\AbstractMigration;

class POCOR7155 extends AbstractMigration
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
        // Creating backup
        $this->execute('CREATE TABLE `zz_7155_institution_students` LIKE `institution_students`');
        $this->execute('INSERT INTO `zz_7155_institution_students` SELECT * FROM `institution_students`');

        //deleting academic period in import compatancy section
        $this->execute('ALTER TABLE `institution_students` DROP FOREIGN KEY insti_stude_fk_previ_insti_stude_id');
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_students`');
        $this->execute('RENAME TABLE `zz_7155_institution_students` TO `institution_students`');
    }
}
