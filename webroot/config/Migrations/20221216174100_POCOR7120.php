<?php
use Migrations\AbstractMigration;

class POCOR7120 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_7120_security_group_users`');
        $this->execute('CREATE TABLE `zz_7120_security_group_users` LIKE `security_group_users`');
       

        // remove foreign key from security_group_users
        $this->execute('ALTER TABLE institution_surveys DROP FOREIGN KEY insti_surve_fk_ass_id;');
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_group_users`');
        $this->execute('RENAME TABLE `zz_7120_security_group_users` TO `security_group_users`');
    }
}