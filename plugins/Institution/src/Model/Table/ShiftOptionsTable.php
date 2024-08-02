<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use ArrayObject;

use App\Model\Table\ControllerActionTable;

class ShiftOptionsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('shift_options');
        parent::initialize($config);
        $this->hasMany('Shifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'shift_option_id']);
        $this->addBehavior('FieldOption.FieldOption');
        $this->toggle('add', true);
        $this->toggle('remove', true);//POCOR-7393 Case 3rd
    }

    public function beforeAction(Event $event)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
        $this->field('start_time', ['after' => 'name']);
        $this->field('end_time', ['after' => 'start_time']);
    }

    public function indexBeforeAction(Event $event)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return __($entity->name);
    }

    public function getStartEndTime($shiftOptionId, $select)
    {
        $data = $this->get($shiftOptionId)->toArray();
        return $data[$select.'_time'];
    }

    public function findAvailableShifts(Query $query, array $options)
    {
        if (isset($options['institution_id']) && isset($options['academic_period_id'])) {
            $conditions = [
                $this->Shifts->aliasField('shift_option_id = ') . $this->aliasField('id'),
                $this->Shifts->aliasField('institution_id') => $options['institution_id'],
                $this->Shifts->aliasField('academic_period_id') => $options['academic_period_id']
            ];
            $query->leftJoin(
                [$this->Shifts->getAlias() => $this->Shifts->getTable()],
                $conditions
            )
            ->where(
                [$this->Shifts->aliasField('id IS NULL')]
            );
        } else {
            pr('fields not exists');die;
        }
        return $query;
    }

    public function findAvailableShiftsOccupier(Query $query, array $options)
    {
        if (isset($options['institution_id']) && isset($options['academic_period_id'])) {
            $conditions = [
                $this->Shifts->aliasField('shift_option_id = ') . $this->aliasField('id'),
                $this->Shifts->aliasField('location_institution_id') => $options['institution_id'],
                $this->Shifts->aliasField('academic_period_id') => $options['academic_period_id']
            ];
            $query->leftJoin(
                [$this->Shifts->getAlias() => $this->Shifts->getTable()],
                $conditions
            )
            ->where(
                [$this->Shifts->aliasField('id IS NULL')]
            );
        } else {
            pr('fields not exists');die;
        }
        return $query;
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
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
            case 'start_time':
                return __('Start Time');
            case 'end_time':
                return __('End Time');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
