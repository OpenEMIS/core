<?php
use Migrations\AbstractMigration;

class DeletedLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('deleted_logs');
        $table->addColumn('id', 'integer', [
            'limit' => 11
        ]);
        $table->addColumn('generated_on', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('generated_by', 'string', [
            'default' => null,
            'null' => false,
        ])
        ->addForeignKey('academic_period_id', 'academic_periods', 'academic_period_id')
        ->save();
    }
}
