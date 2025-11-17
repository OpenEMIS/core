<?php

namespace FieldOption\Model\Table;

//POCOR-8873
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use ArrayObject;

class ItemTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('item_types');
        parent::initialize($config);

        $this->belongsTo('StockUnit', ['className' => 'FieldOption.StockUnits']);
        $this->hasMany('InstitutionConsumable', [
            'className' => 'Institution.InstitutionConsumables',
            'foreignKey' => 'item_type_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('FieldOption.FieldOption');

        // $this->addBehavior('Restful.RestfulAccessControl', [
        //     'Students' => ['index', 'add'],
        //     'Staff' => ['index', 'add']
        // ]);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
    
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['stock_unit_id']['type'] = 'select';

        $this->setFieldOrder(['visible', 'default', 'editable', 'name', 'stock_unit_id', 'international_code', 'national_code']);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            case 'stock_unit_id':
                return __('Stock Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
