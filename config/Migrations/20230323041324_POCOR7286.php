<?php
use Migrations\AbstractMigration;

class POCOR7286 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->execute('ALTER TABLE `special_need_types` MODIFY COLUMN `name` varchar(75) NOT NULL');
        $this->execute('ALTER TABLE `special_need_difficulties` MODIFY COLUMN `name` varchar(75) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_service_types` MODIFY COLUMN `name` varchar(75) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_service_classifications` MODIFY COLUMN `name` varchar(75) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_referrer_types` MODIFY COLUMN `name` varchar(75) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_plan_types` MODIFY COLUMN `name` varchar(75) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_diagnostics_types` MODIFY COLUMN `name` varchar(75) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_device_types` MODIFY COLUMN `name` varchar(75) NOT NULL');

    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('ALTER TABLE `special_need_types` MODIFY COLUMN `name` varchar(50) NOT NULL');
        $this->execute('ALTER TABLE `special_need_difficulties` MODIFY COLUMN `name` varchar(50) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_service_types` MODIFY COLUMN `name` varchar(50) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_service_classifications` MODIFY COLUMN `name` varchar(50) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_referrer_types` MODIFY COLUMN `name` varchar(50) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_plan_types` MODIFY COLUMN `name` varchar(50) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_diagnostics_types` MODIFY COLUMN `name` varchar(50) NOT NULL');
        $this->execute('ALTER TABLE `special_needs_device_types` MODIFY COLUMN `name` varchar(50) NOT NULL');

    }
}
