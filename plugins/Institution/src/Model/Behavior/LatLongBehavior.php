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
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        //POCOR-9257: use the single latitude_longitude config key (1=Mandatory, 0=Non-mandatory, 2=Excluded)
        $LatLongPermission = $ConfigItems->value("latitude_longitude");
        $LongPermission = $LatLongPermission; // same config governs both fields
        $model = $this->_table; //POCOR-8082
        if ($LatLongPermission == self::MANDATORY && $LongPermission == self::MANDATORY) { //POCOR-7045
            $validator = new Validator();
            return $validator->setProvider('custom', $model) //POCOR-8082
                //POCOR-9257: notEmpty enforces mandatory when config latitude_mandatory/longitude_mandatory = 1
                ->notEmptyString('longitude', __('Longitude is required'))
                ->add('longitude', 'ruleLongitude', [
                        'rule' => 'checkLongitude'
                ])
                ->notEmptyString('latitude', __('Latitude is required'))
                ->add('latitude', 'ruleLatitude', [
                    'rule' => 'checkLatitude'
                ])
            ;
        } elseif ($LatLongPermission == self::NON_MANDATORY && $LongPermission == self::NON_MANDATORY) { //POCOR-7045
            $validator = new Validator();
            return $validator->setProvider('custom', $model) //POCOR-8082
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
            $validator->setProvider('custom', $model); //POCOR-8082
            return $validator;
        } else {
            Log::write('error', 'Configuration does not exist. Configuration value: ' . $LatLongPermission);
        }
    }
}