<?php
use Migrations\AbstractMigration;

class POCOR6210 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        //backup
        $this->execute('CREATE TABLE `z_6210_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6210_security_functions` SELECT * FROM `security_functions`');

        $this->execute('CREATE TABLE `z_6210_institution_cases` LIKE `institution_cases`');
        $this->execute('INSERT INTO `z_6210_institution_cases` SELECT * FROM `institution_cases`');

       $this->execute('UPDATE `institution_cases` SET `description` = "" WHERE `description`IS NULL');

        $this->execute('UPDATE `security_functions` SET `_add` = "Cases.add" WHERE `name` = "Cases"');
        $this->execute('ALTER TABLE `institution_cases` CHANGE `description` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL');

    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6210_security_functions` TO `security_functions`');

        $this->execute('DROP TABLE IF EXISTS `institution_cases`');
        $this->execute('RENAME TABLE `z_6210_institution_cases` TO `institution_cases`');
    }
}
