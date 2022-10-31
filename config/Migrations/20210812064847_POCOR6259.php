<?php
use Migrations\AbstractMigration;

class POCOR6259 extends AbstractMigration
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
        //insert into zz_6259_student_behaviour_categories
        $this->execute('CREATE TABLE `zz_6259_student_behaviour_categories` LIKE `student_behaviour_categories`');
        $this->execute('INSERT INTO `zz_6259_student_behaviour_categories` SELECT * FROM `student_behaviour_categories`');
        //insert into zz_6259_behaviour_classifications
        $this->execute('CREATE TABLE `zz_6259_behaviour_classifications` LIKE `behaviour_classifications`');
        $this->execute('INSERT INTO `zz_6259_behaviour_classifications` SELECT * FROM `behaviour_classifications`');

        //change character colmn limit 50 to 170 varchar 
        $this->execute("ALTER TABLE `student_behaviour_categories` CHANGE `name` `name` VARCHAR(170) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
        $this->execute("ALTER TABLE `behaviour_classifications` CHANGE `name` `name` VARCHAR(170) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    public function down() {
        $this->execute('DROP TABLE IF EXISTS `student_behaviour_categories`');
        $this->execute('RENAME TABLE `zz_6259_student_behaviour_categories` TO `student_behaviour_categories`');

        $this->execute('DROP TABLE IF EXISTS `behaviour_classifications`');
        $this->execute('RENAME TABLE `zz_6259_behaviour_classifications` TO `behaviour_classifications`');
    }
}
