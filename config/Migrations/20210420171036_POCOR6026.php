<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR6026 extends AbstractMigration
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
        // Backup table
        $this->execute('DROP TABLE IF EXISTS `zz_6026_config_items`');
        $this->execute('CREATE TABLE `zz_6026_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_6026_config_items` SELECT * FROM `config_items`');
        
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $StudentWithdrawReasons = TableRegistry::get('Student.StudentWithdrawReasons');
        $value = $StudentWithdrawReasons->find()
                ->order([$StudentWithdrawReasons->aliasField('order ASC')])
                ->first();

        if (isset($value)) {
            $id = $value->id;
            $query = $ConfigItems->query();
            $result = $query->update()
                        ->set(['value' => $id, 'default_value' =>$id])
                        ->where(['code' => 'student_withdraw_reasons', 'type' => 'Automated Student Withdrawal'])
                        ->execute();
        }
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_6026_config_items` TO `config_items`');
    }
}
