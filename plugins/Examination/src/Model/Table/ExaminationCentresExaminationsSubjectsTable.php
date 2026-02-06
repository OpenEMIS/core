<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsSubjectsTable extends ControllerActionTable
{
    private $queryString;
    private $examCentreId;

    public function initialize(array $config): void
    {
       
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationSubjects', ['className' => 'Examination.ExaminationSubjects']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsStudents', [
            'className' => 'Examination.ExaminationCentresExaminationsStudents',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_subject_id'],
            'targetForeignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index']
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('search', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {
        $this->queryString = $this->request->getQuery['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'view', 'queryString' => $this->queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Subjects', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Subjects');
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        if (isset($this->examCentreId)) {
            $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
            $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Subjects'));
        }

        $this->field('code', ['sort' => ['field' => 'ExaminationSubjects.code']]);
        $this->field('name', ['sort' => ['field' => 'ExaminationSubjects.name']]);
        $this->field('education_subject_id', ['sort' => ['field' => 'EducationSubjects.name']]);
        $this->field('examination_date', ['type' => 'date', 'sort' => ['field' => 'ExaminationSubjects.examination_date']]);
        $this->field('examination_id', ['type' => 'select']);
        $this->setFieldOrder(['code', 'name', 'education_subject_id', 'examination_date', 'examination_id']);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Exam Centre Subjects','Examinations');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        if (is_null($this->examCentreId)) {
            $event->stopPropagation();
            $this->Alert->error('general.notExists', ['reset' => 'override']);
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        // set queryString for page refresh
        $this->controller->set('queryString', $this->queryString);
        if($this->examCentreId == null){
            $this->examCentreId = 1;
        }
        // Examination filter
        $ExaminationCentresExaminations = $this->ExaminationCentresExaminations;
        $examinationOptions = $this->ExaminationCentresExaminations
            ->find('list', [
                'keyField' => 'examination_id',
                'valueField' => 'examination.code_name'
            ])
            ->contain('Examinations')
            ->where([$ExaminationCentresExaminations->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();

        $examinationOptions = ['-1' => '-- '.__('Select Examination').' --'] + $examinationOptions;
        $selectedExamination = !is_null($this->request->getQuery('examination_id')) ? $this->request->getQuery('examination_id') : -1;
        $this->controller->set(compact('examinationOptions', 'selectedExamination'));
        if ($selectedExamination != -1) {
           $where[$this->aliasField('examination_id')] = $selectedExamination;
        }

        // exam centre controls
        $extra['elements']['controls'] = ['name' => 'Examination.ExaminationCentres/controls', 'data' => [], 'options' => [], 'order' => 1];

        $where[$this->aliasField('examination_centre_id')] = $this->examCentreId;
        $extra['auto_contain_fields'] = ['ExaminationSubjects' => ['code', 'examination_date']];

        $query
            ->contain('EducationSubjects')
            ->where([$where]);

        // sorting columns
        $sortList = ['ExaminationSubjects.code', 'ExaminationSubjects.name', 'ExaminationSubjects.examination_date', 'EducationSubjects.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('weight');
        $this->field('start_time', ['type' => 'time']);
        $this->field('end_time', ['type' => 'time']);
        $this->field('examination_grading_type_id');
        $this->setFieldOrder(['code', 'name', 'education_subject_id',  'examination_date', 'start_time', 'end_time', 'weight', 'examination_grading_type_id', 'examination_id']);
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain('ExaminationSubjects.ExaminationGradingTypes');
    }

    public function onGetName(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->name;
        }

        return $value;
    }

    public function onGetCode(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->code;
        }

        return $value;
    }

    public function onGetExaminationDate(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->examination_date;
        }

        return $value;
    }

    public function onGetWeight(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->weight;
        }

        return $value;
    }

    public function onGetStartTime(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->start_time;
        }

        return $value;
    }

    public function onGetEndTime(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item')) {
            $value = $entity->examination_item->end_time;
        }

        return $value;
    }

    public function onGetExaminationGradingTypeId(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('examination_item') && $entity->examination_item->has('examination_grading_type')) {
            $value = $entity->examination_item->examination_grading_type->code_name;
        }

        return $value;
    }

    public function onGetEducationSubjectId(EventInterface $event, Entity $entity)
    {
        $value = ' ';
        if ($entity->has('education_subject')) {
            $value = $entity->education_subject->name;
        }

        return $value;
    }

    public function getExaminationCentreSubjects($examinationCentreId, $examinationId)
    {
        
        $subjectList = $this
            ->find('list', [
                'keyField' => 'examination_subject_id',
                'valueField' => 'education_subject_id'
            ])
            ->where([
                $this->aliasField('examination_centre_id') => $examinationCentreId,
                $this->aliasField('examination_id') => $examinationId
            ])
            ->toArray();
        return $subjectList;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'examination_id') {
            return __('Examination');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'examination_date') {
            return __('Examination Date');
        } elseif ($field == 'institution_type') {
            return __('Institution Type');
        } elseif ($field == 'education_subject_id') {
            return __('Education Subject');
        } elseif ($field == 'institutions') {
            return __('Institutions');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'code') {
            return __('Code');
        }elseif ($field == 'name') {
            return __('Name');
        }elseif ($field == 'area_id') {
            return __('Area');
        }elseif ($field == 'address') {
            return __('Address');
        }elseif ($field == 'postal_code') {
            return __('Postal Code');
        }elseif ($field == 'contact_person') {
            return __('Contact Person');
        }elseif ($field == 'telephone') {
            return __('Telephone');
        }elseif ($field == 'email') {
            return __('Email');
        }elseif ($field == 'website') {
            return __('Website');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
