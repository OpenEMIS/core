<?php
use Cake\ORM\TableRegistry;
use Migrations\AbstractMigration;

class POCOR4558 extends AbstractMigration
{
    public function up()
    {
        $UserBodyMasses = TableRegistry::get('User.UserBodyMasses')
        ->find()
        ->toArray();
        
        if (!empty($UserBodyMasses)) {
            $sql = '';
            foreach($UserBodyMasses as $key => $value)
            {
                $heightInCentimeters = $value['height'] * 100;

                $sql .= 'UPDATE `user_body_masses` SET `height` = '.$heightInCentimeters.'
                    WHERE `id` = '.$value['id'].';';
            }
            $this->execute($sql);
        }
    }

    public function down()
    {
        $UserBodyMasses = TableRegistry::get('User.UserBodyMasses')
        ->find()
        ->toArray();
        
        if (!empty($UserBodyMasses)) {
            $sql = '';
            foreach($UserBodyMasses as $key => $value)
            {
                $heightInCentimeters = $value['height'] / 100;

                $sql .= 'UPDATE `user_body_masses` SET `height` = '.$heightInCentimeters.'
                    WHERE `id` = '.$value['id'].';';
            }
            $this->execute($sql);
        }
    }
}
