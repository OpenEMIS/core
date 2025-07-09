<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Cache\Cache;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Filesystem\Folder;
use Cake\Log\Log;

class LabelsTable extends AppTable
{
    private $excludeList = ['created_user_id', 'created', 'modified_user_id', 'modified'];
    private $defaultConfig = 'labels';

    public function getLabel($module, $field, $language)
    {
        $label = false;
        $keyFetch = $module.'.'.$field;
        $label = Cache::read($keyFetch, $this->defaultConfig);
        // POCOR-9022 check if label is empty
        if (!$label) {
            if(!$field) {
                $label = __('Not Set');
            }
            if(!$module) {
                $label = __($field);
            }
            if (!empty($field) && !empty($module)) {
                $entity = $this->find()
                    ->where([
                        $this->aliasField('module') => $module,
                        $this->aliasField('field') => $field
                    ])
                    ->first();
                if (!empty($entity)) {
                    $label = $entity->name;
                    $keyValue = self::concatenateLabel($entity);
                    Cache::write($keyFetch, $keyValue, $this->defaultConfig);
                }
            }
        }
        // POCOR-9022 end
        if ($label !== false) {
            $label =  __(ucfirst($label));
        } else {
            //check whether the key is part of the excluded list
            if (in_array($field, $this->excludeList)) {
                $label = Cache::read('General.'.$field, $this->defaultConfig);
            }
        }

        return $label;
    }

    public function storeLabelsInCache()
    {
        // Will clear all keys.
        // Cache::clear(false);

        $cacheFolder = new Folder(CACHE.'labels');
        $files = $cacheFolder->find();
        // ignore hidden files in linux - aka anything that starts with a dot will be ignored
        $filteredFiles = [];
        foreach ($files as $key => $value) {
            if (substr($value, 0, 1)  !== '.') {
                $filteredFiles[] = $value;
            }
        }

        if (empty($filteredFiles)) {
            $keyArray = [];
            $allLabels = $this->find();
            foreach ($allLabels as $eachLabel) {
                $keyCreation = $eachLabel->module.'.'.$eachLabel->field;
                $keyValue = self::concatenateLabel($eachLabel);
                $keyArray[$keyCreation] = $keyValue;
            }
            // echo "<pre>";print_r($keyArray);die;
            //Write multiple to cache
            $result = Cache::writeMany($keyArray, $this->defaultConfig);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        Cache::clear('labels');
        Log::debug('LabelsTable::afterSave()');
        $keyFetch = $entity->module.'.'.$entity->field;
        $keyValue = self::concatenateLabel($entity);
        Log::debug('LabelsTable::afterSave() keyFetch: '.$keyFetch);
        Log::debug('LabelsTable::afterSave() keyValue: '.$keyValue);
        Cache::write($keyFetch, $keyValue, $this->defaultConfig);

    }

    public function concatenateLabel($entity)
    {
        $keyFetch = $entity->module.'.'.$entity->field;
        $keyValue = (!is_null($entity->name) && ($entity->name != "")) ? $entity->name : $entity->field_name;

        if (!is_null($entity->code) && ($entity->code != "")) {
            $keyValue = ucfirst($entity->code).' '.ucfirst($keyValue); // POCOR-4095 Remove the bracket on the label code
        }

        return $keyValue;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //do not save empty strings
        if ($entity->code == "") {
            $entity->code = null;
        }

        if ($entity->name == "") {
            $entity->name = null;
        }
    }

    public function findIndex(Query $query, array $options)
    {
        return $query->where(['visible' => 1]);
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            ->add('code', [
                    'ruleUnique' => [
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                    ]
                ])
            ;
        return $validator;
    }
}
