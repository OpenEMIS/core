<?php
use Migrations\AbstractMigration;

class POCOR5069 extends AbstractMigration
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
        // Backup tables
        $this->execute('CREATE TABLE `zz_5069_institution_positions` LIKE `institution_positions`');
        $this->execute('INSERT INTO `zz_5069_institution_positions` SELECT * FROM `institution_positions`');

        $this->execute('ALTER TABLE institution_positions DROP FOREIGN KEY insti_posit_fk_staff_posit_grade_id');
    }

    public function down()
    {

        $this->execute('DROP TABLE IF EXISTS `institution_positions`');
        $this->execute('RENAME TABLE `zz_5069_institution_positions` TO `institution_positions`');

    }
}
