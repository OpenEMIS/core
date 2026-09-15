<?php
namespace SSO\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;

class AuthenticationTypeAttributesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'modified' => 'existing',
                ],
            ],
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();

        $events['Model.beforeSave'] = [
            'callable' => 'beforeSave',
            'priority' => 5,
        ];

        return $events;
    }

    public function beforeSave(
        EventInterface $event,
        Entity $entity,
        ArrayObject $options
    ): void {
        $userId = null;

        if (
            isset($_SESSION['Auth']) &&
            isset($_SESSION['Auth']['User']) &&
            isset($_SESSION['Auth']['User']['id'])
        ) {
            $userId = $_SESSION['Auth']['User']['id'];
        }

        if ($userId === null) {
            $userId = 0;
        }

        if (!$entity->isNew()) {
            $entity->modified_user_id = $userId;
        } else {
            $entity->created_user_id = $userId;
        }
    }

    public function getTypeAttributeValues(?string $typeName = null): array
    {
        $list = $this->find('list', [
            'groupField' => 'authentication_type',
            'keyField' => 'attribute_field',
            'valueField' => 'value',
        ])
            ->order([
                $this->aliasField('attribute_field'),
            ])
            ->toArray();

        if ($typeName !== null) {
            return $list[$typeName] ?? [];
        }

        return $list;
    }
}