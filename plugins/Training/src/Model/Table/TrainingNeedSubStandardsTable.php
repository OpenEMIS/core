<?php
namespace Training\Model\Table;

use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use ArrayObject;

use App\Model\Table\ControllerActionTable;

class TrainingNeedSubStandardsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('TrainingNeedStandards', ['className' => 'Training.TrainingNeedStandards']);
        $this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds']);

        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $parentFieldOptions = $this->TrainingNeedStandards->find('list')->toArray();
        $selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

        if (!empty($selectedParentFieldOption)) {
            $query->where([$this->aliasField('training_need_standard_id') => $selectedParentFieldOption]);
        }

        $this->controller->set(compact('parentFieldOptions', 'selectedParentFieldOption'));
    }

    public function afterAction(EventInterface $event)
    {
        $this->field('training_need_standard_id', ['type' => 'select']);
    }

    public function onUpdateFieldTrainingNeedStandardId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $query = $this->TrainingNeedStandards
                ->find('list')
                ->find('visible')
                ->order([$this->TrainingNeedStandards->aliasField('order')])
                ->toArray();

        $attr['options'] = $query;
        $attr['type'] = 'select';

        return $attr;
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
            case 'training_need_standard_id':
                return __('Training Need Standard');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
