<?php
use Migrations\AbstractMigration;

class POCOR6816 extends AbstractMigration
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
        $data = [
            'type' => 'Transfer',
            'name' => 'aaa',
            'path' => 'aaa',
            'feature' => 'aaa',
            'from_academic_period_id' => 2,
            'to_academic_period_id' => 2,
            'created_user_id' => 2,
            'created' => date('Y-m-d')
      ];

      $table = $this->table('data_management_logs');
      $table->insert($data);
      $table->saveData();
    }
}
