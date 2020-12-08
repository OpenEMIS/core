<?php
namespace Institution\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;

class LatLongBehavior extends Behavior
{
    const NON_MANDATORY = 0;
    const MANDATORY = 1;
    const EXCLUDED = 2;

    public function LatLongValidation()
    {    
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $LatLongPermission = $ConfigItems->value("latitude_longitude");
        
        if ($LatLongPermission == self::MANDATORY) {
            $validator = new Validator();
            return $validator
                ->requirePresence('longitude')
                ->add('longitude', 'ruleLongitude', [
                        'rule' => 'checkLongitude'
                ])
                ->requirePresence('latitude')
                ->add('latitude', 'ruleLatitude', [
                    'rule' => 'checkLatitude'
                ])
            ;
        } elseif ($LatLongPermission == self::NON_MANDATORY) {
            $validator = new Validator();
            return $validator
                ->allowEmpty('longitude')
                ->add('longitude', 'ruleLongitude', [
                        'rule' => 'checkLongitude'
                    ])
                ->allowEmpty('latitude')
                ->add('latitude', 'ruleLatitude', [
                        'rule' => 'checkLatitude'
                ]);
        } elseif ($LatLongPermission == self::EXCLUDED) {
            $validator = new Validator();
            return $validator;
        } else {
            Log::write('error', 'Configuration does not exist. Configuration value: ' . $LatLongPermission);
        }
    }
}