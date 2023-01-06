<?php

use Phinx\Migration\AbstractMigration;

class POCOR4334 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        // back up tables
        $this->execute('CREATE TABLE `z_4334_institution_positions` LIKE `institution_positions`');
        $this->execute('INSERT INTO `z_4334_institution_positions` SELECT * FROM `institution_positions`');

        $this->execute('CREATE TABLE `z_4334_training_courses_target_populations` LIKE `training_courses_target_populations`');
        $this->execute('INSERT INTO `z_4334_training_courses_target_populations` SELECT * FROM training_courses_target_populations');

        $this->execute('DELETE FROM `institution_positions`
                        WHERE NOT EXISTS (
                            SELECT 1 FROM `staff_position_titles` 
                            WHERE `staff_position_titles`.`id` = `institution_positions`.`staff_position_title_id`
                        );');

        $this->execute('DELETE FROM `training_courses_target_populations`
                        WHERE NOT EXISTS (
                            SELECT 1 FROM `staff_position_titles` 
                            WHERE `staff_position_titles`.`id` = `training_courses_target_populations`.`target_population_id`
                        );');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_positions`');
        $this->execute('DROP TABLE IF EXISTS `training_courses_target_populations`');

        $this->execute('RENAME TABLE `z_4334_institution_positions` TO `institution_positions`');
        $this->execute('RENAME TABLE `z_4334_training_courses_target_populations` TO `training_courses_target_populations`');
    }
}
