<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class EducationSystemsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels']);
        $this->setDeleteStrategy('restrict');
    }

    public function setupFields(Entity $entity)
    {
        $this->field('name');
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    //getting name of academic period
    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    //added academic filter on systme listing
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    //updating type of academic period
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $attr['options'] = $periodOptions;
                $attr['default'] = $selectedPeriod;

            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->academic_period_id;
                $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;
            }
        }
        return $attr;
    }

    public function getSystemOptions($selectedAcademicPeriod)
    {
        $systemOptions = $this
            ->find('list')
            ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $systemOptions;
    }
}
