<?php
use Migrations\AbstractMigration;

class POCOR7931 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up(){
        $this->execute("CREATE TABLE IF NOT EXISTS `zz_7931_training_courses_target_populations` LIKE `training_courses_target_populations`");
        $this->execute("INSERT INTO `zz_7931_training_courses_target_populations` SELECT * FROM `training_courses_target_populations`");
        $this->execute("ALTER TABLE `training_courses_target_populations` DROP FOREIGN KEY train_cours_targe_popul_fk_targe_popul_id");
    }
    public function down(){
        $this->execute("DROP TABLE IF EXISTS `training_courses_target_populations`");
        $this->execute("RENAME TABLE `zz_7931_training_courses_target_populations` TO `training_courses_target_populations`");
    }
}
