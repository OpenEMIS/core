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
        // End

        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_4344_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_4344_locale_contents` SELECT * FROM `locale_contents`');
        // End

        // For locale_contents table
        $this->execute("UPDATE `locale_contents` SET `en` = 'Risks' WHERE `en` = 'Indexes'");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Risks Criterias' WHERE `en` = 'Indexes Criterias'");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Institution Student Risks' WHERE `en` = 'Institution Student Indexes'");
        // End
    }

    public function down()
    {
        // For security_permission
        $this->execute("UPDATE `security_functions` SET `name` = 'Indexes' WHERE `id` = 1055");
        $this->execute("UPDATE `security_functions` SET `name` = 'Indexes' WHERE `id` = 2032");
        $this->execute("UPDATE `security_functions` SET `name` = 'Indexes', `category` = 'Indexes' WHERE `id` = 5066");
        // End

        // Restore locale_contents
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_4344_locale_contents` TO `locale_contents`');
        // End
    }
}
