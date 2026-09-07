<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8211 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('zz_8211_staff_position_grades')) {
            $this->execute('CREATE TABLE `zz_8211_staff_position_grades` LIKE `staff_position_grades`');
            $this->execute('INSERT INTO `zz_8211_staff_position_grades` SELECT * FROM `staff_position_grades`');
        }
        $table = $this->table('staff_position_grades');

        if (!$table->hasColumn('salary')) {
            $table->addColumn('salary', 'decimal', [
                'null' => true,
                'default' => null,
                'precision' => 10,
                'scale' => 2,
                'after' => 'name'
            ])->update();
        }

        $this->table('staff_position_grade_increments', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
        ])
        ->addColumn('id', 'biginteger', [
                'signed' => false,
                'identity' => true,
        ])
        ->addColumn('academic_period_id', 'integer', [
            'comment' => 'References academic_periods.id',
        ])
        ->addColumn('staff_position_grade_id', 'integer', [
            'comment' => 'References staff_position_grades.id',
        ])
        ->addColumn('increment', 'float', [
            'null' => false,
            'default' => 0,
            'comment' => 'Percentage increment (0-100)',
        ])
        ->addColumn('modified_user_id', 'integer', [
                'null' => true,
                'comment' => 'References security_users.id',
        ])
        ->addColumn('modified', 'datetime', [
                'null' => true,
                'comment' => 'References security_users.id',
        ])
        ->addColumn('created_user_id', 'integer', [
                'null' => true,
                'comment' => 'References security_users.id',
        ])
        ->addColumn('created', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
        ])
        
        ->addIndex(['academic_period_id'])
        ->addIndex(['staff_position_grade_id'])
        ->addIndex(['created'], ['name' => 'idx_created'])
        ->create();
    }


    public function down()
    {
        // --- Rollback: If the backup table exists, drop the current table and rename the backup ---

        if ($this->hasTable('zz_8211_staff_position_grades')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `staff_position_grades`');
            $this->execute('RENAME TABLE `zz_8211_staff_position_grades` TO `staff_position_grades`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        $this->execute('DROP TABLE IF EXISTS `staff_position_grade_increments`');
    }
}
