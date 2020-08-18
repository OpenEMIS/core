<?php
use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR5068 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `zz_5068_labels` LIKE `labels`');
        $this->execute('INSERT INTO `zz_5068_labels` SELECT * FROM `labels`');
        // End
		
		$labelsContent = [
            [
                'id' => Text::uuid(),
                'module' => 'Institutions',
                'field' => 'openemis_no',
                'module_name' => 'Institutions',
                'field_name' => 'Fax',
                'name' => 'Mobile',
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
           
        ];
        $this->insert('labels', $labelsContent);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `zz_5068_labels` TO `labels`');
    }
}
