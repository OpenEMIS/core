<?php
use Migrations\AbstractMigration;

class V3120 extends AbstractMigration
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
		$table = $this->table('institution_students_report_cards');
        $table->addColumn('pdf_file_content', 'blob', [
            'default' => null,
			'limit' => 4294967295,
			'null' => false,
        ])
        ->update();
    }
}
