<?php

namespace FieldOption\Model\Table;

//POCOR-8873
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use ArrayObject;

class StockUnitsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('stock_units');
        parent::initialize($config);

        $this->hasMany('ItemTypes', ['className' => 'FieldOption.ItemTypes', 'foreignKey' => 'stock_unit_id','dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionConsumable', [
            'className' => 'Institution.InstitutionConsumables',
            'foreignKey' => 'stock_unit_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('FieldOption.FieldOption');
        // $this->addBehavior('Restful.RestfulAccessControl', [
        //     'Students' => ['index', 'add'],
        //     'Staff' => ['index', 'add']
        // ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}
