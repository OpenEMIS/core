<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupCoordinatesBehavior extends SetupBehavior
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function editAfterQuery(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $fieldType = '';
        $requestData = $this->_table->request->getData();
        $alias = $this->_table->getAlias();
        if (!empty($requestData)) {
            $fieldType = (isset($requestData[$alias]['field_type']))? $requestData[$alias]['field_type']: null;
        } else {
            if (!empty($entity)) {
                $fieldType = $entity->field_type;
            }
        }
    }
}
