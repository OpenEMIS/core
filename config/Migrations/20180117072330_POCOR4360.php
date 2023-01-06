<?php

use Phinx\Migration\AbstractMigration;

class POCOR4360 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_4360_institution_survey_answers` LIKE `institution_survey_answers`');
        $this->execute('INSERT INTO `z_4360_institution_survey_answers`
                        SELECT * FROM `institution_survey_answers`
                        WHERE (
                            `text_value` IS NULL AND
                            `number_value` IS NULL AND
                            `decimal_value` IS NULL AND
                            `textarea_value` IS NULL AND
                            `date_value` IS NULL AND
                            `time_value` IS NULL AND
                            `file` IS NULL
                        )');

        $this->execute('DELETE FROM `institution_survey_answers`
                        WHERE (
                            `text_value` IS NULL AND
                            `number_value` IS NULL AND
                            `decimal_value` IS NULL AND
                            `textarea_value` IS NULL AND
                            `date_value` IS NULL AND
                            `time_value` IS NULL AND
                            `file` IS NULL
                        )');
    }

    public function down()
    {
        $this->execute('INSERT INTO `institution_survey_answers`
                        SELECT * FROM `z_4360_institution_survey_answers`
                        ');

        $this->execute('DROP TABLE IF EXISTS `z_4360_institution_survey_answers`');
    }
}
