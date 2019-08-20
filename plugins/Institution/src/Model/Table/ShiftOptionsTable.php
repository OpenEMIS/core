<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class ShiftOptionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('shift_options');
        parent::initialize($config);
        $this->hasMany('Shifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'shift_option_id']);
        $this->addBehavior('FieldOption.FieldOption');
        $this->toggle('add', true);
        $this->toggle('remove', false);
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
        if (array_key_exists('institution_id', $options) && array_key_exists('academic_period_id', $options)) {
            $conditions = [
                $this->Shifts->aliasField('shift_option_id = ') . $this->aliasField('id'),
                $this->Shifts->aliasField('institution_id') => $options['institution_id'],
                $this->Shifts->aliasField('academic_period_id') => $options['academic_period_id']
            ];
            $query->leftJoin(
                [$this->Shifts->alias() => $this->Shifts->table()],
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
}
