<?php
use Migrations\AbstractMigration;

class POCOR7126 extends AbstractMigration
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
        // Increase column size from 1000 to 1500
        $this->execute('ALTER TABLE outcome_criterias MODIFY outcome_criterias.name varchar(1500)');
        
    }
         
    // rollback
    public function down()
    {
        // Restore previous configurations
        $this->execute('ALTER TABLE outcome_criterias MODIFY outcome_criterias.name varchar(1000)');

    }
}
?>
