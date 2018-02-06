<?php

use Phinx\Migration\AbstractMigration;

class POCOR2098 extends AbstractMigration
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
        $this->execute("DROP TABLE IF EXISTS `survey_forms_filters`");
        $this->execute("
            CREATE TABLE `survey_forms_filters` (
                `id` char(36) NOT NULL PRIMARY KEY,
                `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id',
                `survey_filter_id` int(11) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains groups of surveys by filter types' ROW_FORMAT=DYNAMIC;
        ");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `survey_forms_filters`');
    }
}
