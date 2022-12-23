<?php
use Migrations\AbstractMigration;

class POCOR7062 extends AbstractMigration
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
        /** backup */
        $this->execute('CREATE TABLE `zz_7062_special_needs_diagnostics_degree` LIKE `special_needs_diagnostics_degree`');
        $this->execute('INSERT INTO `zz_7062_special_needs_diagnostics_degree` SELECT * FROM `special_needs_diagnostics_degree`');

        $this->execute('CREATE TABLE `zz_7062_user_special_needs_diagnostics` LIKE `user_special_needs_diagnostics`');
        $this->execute('INSERT INTO `zz_7062_user_special_needs_diagnostics` SELECT * FROM `user_special_needs_diagnostics`');

        $this->execute('ALTER TABLE `special_needs_diagnostics_degree` ADD FOREIGN KEY (`special_needs_diagnostics_types_id`) REFERENCES `special_needs_diagnostics_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');


        $this->execute('ALTER TABLE `user_special_needs_diagnostics` ADD FOREIGN KEY (`special_needs_diagnostics_degree_id`) REFERENCES `special_needs_diagnostics_degree`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

        $this->execute('ALTER TABLE `user_special_needs_diagnostics` ADD FOREIGN KEY (`special_needs_diagnostics_type_id`) REFERENCES `special_needs_diagnostics_types`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `special_needs_diagnostics_degree`');
        $this->execute('RENAME TABLE `zz_7062_special_needs_diagnostics_degree` TO `special_needs_diagnostics_degree`');

        $this->execute('DROP TABLE IF EXISTS `user_special_needs_diagnostics`');
        $this->execute('RENAME TABLE `zz_7062_user_special_needs_diagnostics` TO `user_special_needs_diagnostics`');
    }
}
