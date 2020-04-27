<?php
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR5291 extends AbstractMigration
{
    public function up()
    {
    	// backup the table
        $this->execute('CREATE TABLE `z_5291_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_5291_labels` SELECT * FROM `labels`');

        $this->execute('UPDATE `labels` SET `module_name` = "Institutions -> Performance -> Report Cards" WHERE `module` = "ReportCardStatuses"');

        $table = $this->table('labels');

        $data = [
            [
                'id' => Text::uuid(),
                'module' => 'ReportCardStatuses',
                'field' => 'report_card',
                'module_name' => 'Institutions -> Performance -> Report Cards',
                'field_name' => 'Report Card',
                'code' => NULL,
                'name' => NULL,
                'visible' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('labels', $data);
    }

    public function down() {
		//Restore backups
        $this->execute('DROP TABLE labels');
        $this->table('z_5291_labels')->rename('labels');
    }
}