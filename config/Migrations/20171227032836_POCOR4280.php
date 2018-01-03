<?php
use Migrations\AbstractMigration;

class POCOR4280 extends AbstractMigration
{

    public function up()
    {
        $table = $this->table('institution_student_withdraw');
        $table->changeColumn('comment', 'text', [
                'null' => false
            ])->save();
    }

    public function down()
    {
        $table = $this->table('institution_student_withdraw');
        $table->changeColumn('comment', 'text', [
                'null' => true
            ])->save();
    }
}
