<?php
use Migrations\AbstractMigration;

class POCOR5170 extends AbstractMigration
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
        $this->insert('security_functions', [
            'name' => 'Institution Profile Completness',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'General',
            'parent_id' => 8,
            '_view' => 'InstitutionProfileCompletness.view',
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => NULL,
            'order' => 1,
            'visible' => 1,
            'description' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('DELETE FROM security_functions WHERE name = "Institution Profile Completness"');
    }
}
