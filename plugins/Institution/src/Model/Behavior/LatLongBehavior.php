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
        //POCOR-9257: keep legacy latitude_mandatory/longitude_mandatory in sync so old code never diverges
        $ConfigItems->updateAll(['value' => $LatLongPermission], ['code IN' => ['latitude_mandatory', 'longitude_mandatory']]);
        $model = $this->_table; //POCOR-8082
        if ($LatLongPermission == self::MANDATORY && $LongPermission == self::MANDATORY) { //POCOR-7045
            $validator = new Validator();
            return $validator->setProvider('custom', $model) //POCOR-8082
                //POCOR-9607[START]
                // ->allowEmpty('longitude')
                ->requirePresence('longitude')->notEmptyString('longitude')
                ->requirePresence('latitude')->notEmptyString('latitude')
                //POCOR-9607[END]
                ->add('longitude', 'ruleLongitude', [
                        'rule' => 'checkLongitude'
                ])
                // ->allowEmpty('latitude') //POCOR-9607
                ->add('latitude', 'ruleLatitude', [
                    'rule' => 'checkLatitude'
                ])
                ////POCOR-9607[START]
                ->add('latitude', [
                    'ruleForLatitudeLength' => [
                        'rule' => ['forLatitudeLength'],
                        'message' => __('Latitude length is incomplete')
                    ]
                ])
                ->add('longitude', [
                    'ruleForLongitudeLength' => [
                        'rule' => ['forLongitudeLength'],
                        'message' => __('Longitude length is incomplete')
                    ]
                ])
                //POCOR-9607[END]
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
