<?php

use Phinx\Migration\AbstractMigration;

class POCOR4344 extends AbstractMigration
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
        // For security_permission
        $this->execute("UPDATE `security_functions` SET `name` = 'Risks' WHERE `id` = 1055");
        $this->execute("UPDATE `security_functions` SET `name` = 'Risks' WHERE `id` = 2032");
        $this->execute("UPDATE `security_functions` SET `name` = 'Risks', `category` = 'Risks' WHERE `id` = 5066");
        // For translation table
        $this->execute("UPDATE `locale_contents` SET `en` = 'Risks' WHERE `id` = 2550");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Risks Criterias' WHERE `id` = 2552");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Institution Student Risks' WHERE `id` = 2553");
    }

    public function down()
    {
        // For security_permission
        $this->execute("UPDATE `security_functions` SET `name` = 'Indexes' WHERE `id` = 1055");
        $this->execute("UPDATE `security_functions` SET `name` = 'Indexes' WHERE `id` = 2032");
        $this->execute("UPDATE `security_functions` SET `name` = 'Indexes', `category` = 'Indexes' WHERE `id` = 5066");
        // For translation table
        $this->execute("UPDATE `locale_contents` SET `en` = 'Indexes' WHERE `id` = 2550");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Indexes Criterias' WHERE `id` = 2552");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Institution Student Indexes'  WHERE `id` = 2553");
    }
}
