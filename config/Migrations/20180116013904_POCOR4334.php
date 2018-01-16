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
        $this->execute('CREATE TABLE `z_4334_training_courses_target_populations` LIKE `training_courses_target_populations`');

        // insert all orphan records to the backup table from institution_positions
        $this->execute('INSERT INTO `z_4334_institution_positions`
                        SELECT * FROM `institution_positions`
                        WHERE `staff_position_title_id` 
                        NOT IN ( 
                            SELECT `id` FROM `staff_position_titles`
                        )');

        // delete all records from the original table from institution_positions
        $this->execute('DELETE FROM `institution_positions`
                        WHERE `staff_position_title_id`
                        NOT IN (
                            SELECT `id` FROM `staff_position_titles`
                        )');

        // insert all orphan records to the backup table from training_courses_target_populations
        $this->execute('INSERT INTO `z_4334_training_courses_target_populations`
                        SELECT * FROM `training_courses_target_populations`
                        WHERE `target_population_id`
                        NOT IN (
                            SELECT `id` FROM `staff_position_titles`
                        )');

        // delete all records from the original table from training_courses_target_populations
        $this->execute('DELETE FROM `training_courses_target_populations`
                        WHERE `target_population_id`
                        NOT IN (
                            SELECT `id` FROM `staff_position_titles`
                        )');
    }

    public function down()
    {
        $this->execute('INSERT INTO `institution_positions` 
                        SELECT * FROM `z_4334_institution_positions`');

        $this->execute('INSERT INTO `training_courses_target_populations`
                        SELECT * FROM `z_4334_training_courses_target_populations`');

        $this->execute('DROP TABLE IF EXISTS `z_4334_institution_positions`');
        $this->execute('DROP TABLE IF EXISTS `z_4334_training_courses_target_populations`');
    }
}
