<?php
use Migrations\AbstractMigration;

class POCOR3897 extends AbstractMigration
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
       // institution_staff_shifts
        $table = $this->table('institution_staff_shifts', [
                'collation' => 'utf8mb4_unicode_ci',
            ]);
        $table->addColumn('staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('shift_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
           ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
           ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->save();
       //  end institution_staff_shifts
        
        $this->execute('CREATE TABLE `z_3897_institution_staff_attendances` LIKE `institution_staff_attendances`');
        $this->execute('INSERT INTO `z_3897_institution_staff_attendances` SELECT * FROM `institution_staff_attendances`');
        $this->execute('ALTER TABLE `institution_staff_attendances` ADD COLUMN absence_type_id integer default 1 AFTER created'); 
        
        $this->execute('UPDATE `institution_shifts`
            INNER JOIN `institution_staff_attendances`
            ON `institution_staff_attendances`.time_in > `institution_shifts`.start_time OR `institution_staff_attendances`.time_in = `institution_shifts`.start_time 
            INNER JOIN `institution_staff`
            ON `institution_staff`.staff_id = `institution_staff_attendances`.staff_id
            INNER JOIN `institution_staff_shifts`
            ON `institution_staff_shifts`.shift_id = `institution_shifts`.id
            SET `institution_staff_attendances`.absence_type_id = CASE
            WHEN `institution_staff_attendances`.time_in = `institution_shifts`.start_time   THEN 1 
            WHEN `institution_staff_attendances`.time_in > `institution_shifts`.start_time   THEN 3
            END
            WHERE  `institution_staff_attendances`.absence_type_id = 1');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff_attendances`');
        $this->execute('RENAME TABLE `z_3897_institution_staff_attendances` TO `institution_staff_attendances`');  
    }
}