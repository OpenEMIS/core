<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class ExaminationResultsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('examination_centres_examinations');
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportResults']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Results',
                'academic_period_id' => $entity->academic_period_id,
                'examination_id' => $entity->examination_id,
                'examination_centre_id' => $entity->examination_centre_id
            ];
        }

        return $buttons;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_id', ['type' => 'string', 'sort' => false]);
        $this->field('name', ['sort' => ['field' => 'ExaminationCentres.name']]);
        $this->setFieldOrder(['name', 'academic_period_id', 'examination_id', 'total_registered']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];

        $where = [];
        // Academic Period
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();

        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        // End

        // Examination
        $examinationOptions = $this->getExaminationOptions($selectedAcademicPeriod);
        $examinationOptions = ['-1' => __('All Examinations')] + $examinationOptions;
        $selectedExamination = !is_null($this->request->query('examination_id')) ? $this->request->query('examination_id') : -1;

        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }
        // End

        $extra['auto_contain_fields'] = ['ExaminationCentres' => ['code']];
        $query->where($where);

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $extra['OR'] = [
                [$this->ExaminationCentres->aliasField('name').' LIKE' => '%' . $search . '%'],
                [$this->ExaminationCentres->aliasField('code').' LIKE' => '%' . $search . '%']
            ];
        }

        // sort
        $sortList = ['ExaminationCentres.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->examination_centre->code_name;
    }

    private function getExaminationOptions($selectedAcademicPeriod)
    {
        $examinationOptions = $this->Examinations
            ->find('list')
            ->where([$this->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
            ->toArray();

        return $examinationOptions;
    }
}
