<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;

class StudentGpaTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_students_gpa');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $serverRequest = $this->request;

        // Academic period filter
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $academicPeriodOptions = $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $serverRequest->getQuery('academic_period_id') : $this->EducationCycles->EducationLevels->EducationSystems->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$EducationSystems->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        //level filter
        $levelOptions = $this->EducationCycles->EducationLevels->getEducationLevelOptions($selectedAcademicPeriod);
        if (!empty($levelOptions)) {
            $selectedLevel = !empty($serverRequest->getQuery('level')) ? $serverRequest->getQuery('level') : key($levelOptions);
        } else{
            $levelOptions = ['0' => '-- '.__('No Education Level').' --'] + $levelOptions;
            $selectedLevel = !empty($serverRequest->getQuery('level')) ?$serverRequest->getQuery('level') : 0;
        }

        $this->controller->set(compact('levelOptions', 'selectedLevel'));

        $cycleOptions = $this->EducationCycles
                ->find('list')
                ->find('visible')
                ->find('order')
                ->where([$this->EducationCycles->aliasField('education_level_id') => $selectedLevel])
                ->toArray();
        $selectedCycle = !is_null($serverRequest->getQuery('cycle')) ? $serverRequest->getQuery('cycle') : key($cycleOptions);

        $cycleOptions = $cycleOptions;
        if (!empty($cycleOptions)) {
            $selectedCycle = !empty($serverRequest->getQuery('cycle')) ? $serverRequest->getQuery('cycle') : key($cycleOptions);
        } else{
            $cycleOptions = ['0' => '-- '.__('No Education Cycle').' --'] + $cycleOptions;
            $selectedCycle = !empty($serverRequest->getQuery('cycle')) ? $serverRequest->getQuery('cycle') : 0;
        }

        $this->controller->set(compact('cycleOptions', 'selectedCycle'));
        $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where([$this->aliasField('education_cycle_id') => $selectedCycle])
                        ->order([$this->aliasField('order') => 'ASC']);



        $sortList = ['order','code', 'name', 'EducationFieldOfStudies.name', 'EducationCycles.name', 'EducationCertifications.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function indexAfterAction(Event $event, $data)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StudentGpa');
    }

    
}
