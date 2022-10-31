<?php
use Migrations\AbstractMigration;

class POCOR5459 extends AbstractMigration
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
        $table = $this->table('student_attendance_per_day_periods', [
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        $table->addColumn('order', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false
        ])
        ->save();
    }

    // rollback
    public function down()
    {
        
    }
}
