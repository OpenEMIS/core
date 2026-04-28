<?php

namespace System\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class StaffSalariesTable extends ControllerActionTable
{
    private $fieldsOrder = ['name'];
    public function initialize(array $config): void
    {
        $this->setTable('staff_position_grade_increments');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades', 'foreignKey' => 'staff_position_grade_id']);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
        $this->controller->Navigation->substituteCrumb(__('StaffSalaries'), $header);
        $this->controller->Navigation->substituteCrumb(__('Systems'), __('Staff'));
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr,  $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
        }
        return $attr;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }
    
    public function onGetIncrement(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('increment')) {
            $value = number_format($entity->increment, 2) . '%';
        }
        return $value;
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setfieldOrder($this->fieldsOrder);
    }
    private function setupFields(Entity $entity)
    {
        $this->field('id', [
            'type' => 'hidden',
        ]);
        $academicPeriodOptions = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getYearList();
        $this->field('academic_period_id', ['options' => $academicPeriodOptions, 'onChangeReload' => 'changeAcademicPeriodId','attr' => ['required' => true]]);

        $staffPositionGrades = TableRegistry::getTableLocator()->get('Institution.StaffPositionGrades');
        $staffPositionGradesData = $staffPositionGrades->find('list',['keyField' => 'id', 'valueField' => 'name'])->toArray();
        $this->field('staff_position_grade_id', [
            'type' => 'select',
            'attr' => ['required' => true],
            'after' => 'academic_period_id',
            'options' => $staffPositionGradesData
        ]);
    }
    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $this->setupFields($entity);
        $this->field('created_user_id', ['visible' => true]);
        $this->field('created', ['visible' => true, 'sort' => true]);
        $this->field('modified_user_id', ['visible' => true, 'enable' => false]);
        $this->field('modified', ['visible' => true, 'sort' => true]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('academic_period_id')
            ->requirePresence('staff_position_grade_id')
            ->numeric('increment', 'Increment must be a number')
            ->greaterThanOrEqual('increment', 0, 'Minimum value is 0')
            ->lessThanOrEqual('increment', 100, 'Maximum value is 100');
    }


}
