<?php
use Migrations\AbstractMigration;

class POCOR5890 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_5890_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5890_security_functions` SELECT * FROM `security_functions`');
        $this->execute('CREATE TABLE `zz_5890_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `zz_5890_locale_contents` SELECT * FROM `locale_contents`');
        // End

        $this->execute("UPDATE `security_functions` SET `name` = 'Vaccinations' WHERE `name` = 'Immunizations'");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Vaccinations' WHERE `en` = 'Immunizations'");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Health Vaccinations Type' WHERE `en` = 'Health Immunization Type'");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Health Vaccinations' WHERE `en` = 'Health Immunizations'");
        $this->execute("UPDATE `locale_contents` SET `en` = 'Vaccinations Types' WHERE `en` = 'Immunization Types'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5890_security_functions` TO `security_functions`');
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_5890_locale_contents` TO `locale_contents`');
    }
}
