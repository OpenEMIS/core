<?php

use Phinx\Migration\AbstractMigration;

class POCOR7095 extends AbstractMigration
{
    public function up()
    {
        // create backup for table institution_subject_staff
        $this->execute('DROP TABLE IF EXISTS `z_7095_institution_subject_staff`');
        $this->execute('CREATE TABLE `z_7095_institution_subject_staff` LIKE `institution_subject_staff`');
        $this->execute('INSERT INTO `z_7095_institution_subject_staff` SELECT * FROM `institution_subject_staff`');

        // create backup for table institutions
        $this->execute('DROP TABLE IF EXISTS `z_7095_institutions`');
        $this->execute('CREATE TABLE `z_7095_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `z_7095_institutions` SELECT * FROM `institutions`');

        // add index on table institution_subject_staff and institutions
        $this->execute("ALTER TABLE `institution_subject_staff` ADD INDEX( `start_date`, `end_date`)");
        $this->execute("ALTER TABLE `institutions` ADD INDEX(`name`)");
    }

    public function down()
    {
        // undo table institution_subject_staff
        $this->execute('DROP TABLE IF EXISTS `institution_subject_staff`');
        $this->execute('RENAME TABLE `z_7095_institution_subject_staff` TO `institution_subject_staff`');

        // undo table institutions
        $this->execute('DROP TABLE IF EXISTS `institutions`');
        $this->execute('RENAME TABLE `z_7095_institutions` TO `institutions`');
    }
}
