<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Utility\Security;

class CompositeKeyBehavior extends Behavior
{
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $model = $this->_table;
            $primaryKey = $model->getPrimaryKey();
            $hashString = [];

            foreach ($primaryKey as $key) {
                $hashString[] = $entity->{$key};
            }
            $entity->id = Security::hash(implode(',', $hashString), 'sha256');
        }
    }
}
