<?php
use Migrations\AbstractMigration;

class POCOR7081 extends AbstractMigration
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
        // Make security_group_user_id NOT NULLable
        $this->execute('ALTER TABLE institution_staff DROP FOREIGN KEY insti_staff_fk_secur_group_user_id;');
        
    }
         
    // rollback
    public function down()
    {
        // Restore previous configurations
        $this->execute('ALTER TABLE institution_staff ADD CONSTRAINT `insti_staff_fk_secur_group_user_id` FOREIGN KEY (`security_group_user_id`) REFERENCES security_group_users(`id`);');

    }
}
?>
