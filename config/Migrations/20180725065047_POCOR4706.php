<?php

use Phinx\Migration\AbstractMigration;

class POCOR4706 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4706_institution_classes` LIKE `institution_classes`');
        $this->execute('INSERT INTO `z_4706_institution_classes` SELECT * FROM `institution_classes` 
                    WHERE NOT EXISTS(
                        SELECT 1 FROM `institution_shifts` 
                        WHERE `institution_shifts`.`id` = `institution_classes`.`institution_shift_id`
                    )');
        
        $this->execute('UPDATE `institution_classes` SET `institution_shift_id` = (SELECT `id` FROM `institution_shifts` 
                    WHERE `institution_shifts`.`institution_id` = `institution_classes`.`institution_id` ORDER BY `id` LIMIT 1)
                        WHERE NOT EXISTS(
                            SELECT 1 FROM `institution_shifts` 
                            WHERE `institution_shifts`.`id` = `institution_classes`.`institution_shift_id`
                         )');
    }
    
    public function down()
    {
        $this->execute('UPDATE `institution_classes` 
            INNER JOIN `z_4706_institution_classes` ON institution_classes.id = z_4706_institution_classes.id
            SET institution_classes.institution_shift_id = z_4706_institution_classes.institution_shift_id');

        $this->execute('DROP TABLE IF EXISTS `z_4706_institution_classes`');

    }
}
