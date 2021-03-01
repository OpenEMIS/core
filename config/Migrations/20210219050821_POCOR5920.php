<?php
use Migrations\AbstractMigration;

class POCOR5920 extends AbstractMigration
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
		//rollback of institution_report_card_processes
        $this->execute('DROP TABLE IF EXISTS `institution_report_card_processes`');
		
		//institution_report_card_processes
        $this->table('institution_report_card_processes', [
			'id' => false,
            'collation' => 'utf8mb4_unicode_ci',
			'primary_key' => ['report_card_id', 'institution_id'],
        ])
        ->addColumn('report_card_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to report_cards.id'
        ])
        ->addColumn('status', 'integer', [
            'limit' => 2,
            'null' => false,
            'comment' => '1 => New 2 => Running 3 => Completed -1 => Error'
        ])
		->addColumn('institution_id', 'integer', [
			'limit' => 11,
			'null' => false,
			'comment' => 'links to institutions.id'
		])
		->addColumn('academic_period_id', 'integer', [
            'limit' => 11,
            'null' => false,
            'comment' => 'links to academic_periods.id'
        ])
		->addColumn('created', 'datetime', [
			'default' => null,
			'null' => false
		])
		->addIndex('academic_period_id')
		->addIndex('institution_id')
		->addIndex('report_card_id')
        ->save();
		
		$this->execute('ALTER TABLE `institution_report_cards` CHANGE `file_name` `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL');

        $this->execute('ALTER TABLE `institution_report_cards` CHANGE `file_content` `file_content` LONGBLOB NULL DEFAULT NULL');

        $this->execute('ALTER TABLE `institution_report_cards` CHANGE `file_content_pdf` `file_content_pdf` LONGBLOB NULL DEFAULT NULL');
				
        $this->execute('ALTER TABLE `institution_report_cards` CHANGE `started_on` `started_on` DATETIME NULL DEFAULT NULL');

        $this->execute('ALTER TABLE `institution_report_cards` CHANGE `completed_on` `completed_on` DATETIME NULL DEFAULT NULL');
    }

}
