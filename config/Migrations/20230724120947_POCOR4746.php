<?php
use Migrations\AbstractMigration;

class POCOR4746 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_4746_education_programmes` LIKE `education_programmes`');
        $this->execute('INSERT INTO `zz_4746_education_programmes` SELECT * FROM `education_programmes`');
        $this->execute("ALTER TABLE `education_programmes` ADD COLUMN same_grade_promotion  VARCHAR(11) DEFAULT 'no' COMMENT 'yes=enabled,no=disabled' AFTER education_certification_id");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `education_programmes`');
        $this->execute('RENAME TABLE `zz_4746_education_programmes` TO `education_programmes`');
    }
}
