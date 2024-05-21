<?php
use Migrations\AbstractMigration;

class POCOR7121 extends AbstractMigration
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
        $this->execute('ALTER TABLE student_status_updates DROP FOREIGN KEY stude_statu_updat_fk_statu_id;');
        $this->execute('ALTER TABLE student_status_updates ADD CONSTRAINT `stude_statu_updat_fk_statu_id` FOREIGN KEY (`status_id`) REFERENCES student_statuses(`id`);');
        
    }
         
    // rollback
    public function down()
    {
        // Restore previous configurations
        $this->execute('ALTER TABLE student_status_updates DROP FOREIGN KEY stude_statu_updat_fk_statu_id;');
        $this->execute('ALTER TABLE student_status_updates ADD CONSTRAINT `stude_statu_updat_fk_statu_id` FOREIGN KEY (`status_id`) REFERENCES workflow_steps(`id`);');

    }
}
?>
