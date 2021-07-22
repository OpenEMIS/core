<?php
use Migrations\AbstractMigration;

class POCOR5294 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_5294_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_5294_labels` SELECT * FROM `labels`');

        //insert
        $labelsData = [
            [
                'module' => 'InstitutionStudentsCounselling',
                'field' => 'requester',
                'module_name' => 'Institution->Students->Counselling',
                'field_name' => 'Requester',
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('labels', $labelsData);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_5294_labels` TO `labels`');
    }
}
