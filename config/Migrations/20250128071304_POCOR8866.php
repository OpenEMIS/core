<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8866 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_8866_student_behaviour_categories` LIKE `student_behaviour_categories`');
        $this->execute('INSERT INTO `zz_8866_student_behaviour_categories` SELECT * FROM `student_behaviour_categories`');
        $this->execute('ALTER TABLE `student_behaviour_categories` DROP FOREIGN KEY `stude_behav_categ_fk_beh_cla_id`');
        $this->execute('ALTER TABLE `student_behaviour_categories` DROP COLUMN  `behaviour_classification_id`');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_behaviour_categories`');
        $this->execute('RENAME TABLE `zz_8866_student_behaviour_categories` TO `student_behaviour_categories`');
    }
}
