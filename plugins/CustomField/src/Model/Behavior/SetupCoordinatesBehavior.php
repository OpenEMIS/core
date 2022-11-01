<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupCoordinatesBehavior extends SetupBehavior
{

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function editAfterQuery(Event $event, Entity $entity, ArrayObject $extra)
    {
        $fieldType = '';
        $requestData = $this->_table->request->data;
        $alias = $this->_table->alias();
        if (!empty($requestData)) {
            $fieldType = (array_key_exists('field_type', $requestData[$alias]))? $requestData[$alias]['field_type']: null;
        } else {
            if (!empty($entity)) {
                $fieldType = $entity->field_type;
            }
        }
    }
}
