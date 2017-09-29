<?php

use Phinx\Migration\AbstractMigration;

class POCOR3710 extends AbstractMigration
{
    // commit
    public function up()
    {
        $table = $this->table('security_users');
        $table->addIndex(['is_student', 'first_name', 'last_name', 'gender_id', 'date_of_birth'], [
                'name' => 'student'
            ])
            ->save();
    }

    // rollback
    public function down()
    {
        $table = $this->table('security_users');
        $table->removeIndexByName('student');
        // $table->removeIndex(['is_student', 'first_name', 'last_name', 'gender_id', 'date_of_birth']);
    }
}
