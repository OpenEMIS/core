<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;

class InstitutionExaminationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
   
        $this->setTable('examinations');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ExaminationSubjects', ['className' => 'Examination.ExaminationSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('ExaminationCentres', [
            'className' => 'Examination.ExaminationCentres',
            'joinTable' => 'examination_centres_examinations',
            'foreignKey' => 'examination_id',
            'targetForeignKey' => 'examination_centre_id',
            'through' => 'Examination.ExaminationCentresExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('ExaminationCentreRooms', [
            'className' => 'Examination.ExaminationCentreRooms',
            'joinTable' => 'examination_centre_rooms_examinations',
            'foreignKey' => 'examination_id',
            'targetForeignKey' => 'examination_centre_room_id',
            'through' => 'Examination.ExaminationCentreRoomsExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Excel', ['pages' => ['index']]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Institution.Examinations/controls', 'data' => [], 'options' => [], 'order' => 1];

        $this->field('description', ['visible' => 'hidden']);
        $this->setFieldOrder(['academic_period_id', 'code', 'name', 'education_grade_id']);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Exams','Examinations');       
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

     public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
     {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        // Academic Periods filter
        $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
        $selectedPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));

        $where[$this->aliasField('academic_period_id')] = $selectedPeriod;
        //End

        // get available grades in institution
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $educationGrades = $InstitutionGrades
            ->find('list', [
                    'keyField' => 'education_grade_id',
                    'valueField' => 'education_grade_id'
            ])
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->toArray();

        if (!empty($educationGrades)) {
            $where[$this->aliasField('education_grade_id IN')] = $educationGrades;
            $query->where($where);

        } else {
            // if no active grades in the institution
            $this->Alert->warning($this->aliasField('noGrades'));
            $event->stopPropagation();
        }
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationSubjects.EducationSubjects', 'ExaminationSubjects.ExaminationGradingTypes']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_subjects', [
            'type' => 'element',
            'element' => 'Examination.examination_subjects'
        ]);

        $this->setFieldOrder(['academic_period_id', 'code', 'name', 'description', 'education_grade_id', 'registration_start_date', 'registration_end_date', 'examination_subjects']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
     
        $extraField[] = [
            'key' => 'InstitutionExaminations.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Code')
        ];

        $extraField[] = [
            'key'   => 'InstitutionExaminations.name',
            'field' => 'name',
            'type'  => 'string',
            'label' => __('Name')
        ];

        $extraField[] = [
            'key'   => 'InstitutionExaminations.registration_start_date',
            'field' => 'registration_start_date',
            'type'  => 'date',
            'label' => __('Registration Start Date')
        ];

        $extraField[] = [
            'key'   => 'InstitutionExaminations.registration_end_date',
            'field' => 'registration_end_date',
            'type'  => 'date',
            'label' => __('Registration End Date')
        ];

        $extraField[] = [
            'key' => 'EducationGrades.code',
            'field' => 'grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraField[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $academicPeriod = $this->request->getQuery['academic_period_id']; 
        
            $query
            ->select(['code' => 'InstitutionExaminations.code', 'name' => 'InstitutionExaminations.name', 'grade' => 'EducationGrades.code', '	registration_start_date' => 'InstitutionExaminations.registration_start_date',  'registration_end_date' => 'InstitutionExaminations.registration_end_date', 'academic_period' => 'AcademicPeriods.name'])
            ->LeftJoin([$this->EducationGrades->getAlias() => $this->EducationGrades->getTable()],[
                $this->EducationGrades->aliasField('id').' = ' . 'InstitutionExaminations.education_grade_id'
            ])
            ->LeftJoin([$this->AcademicPeriods->getAlias() => $this->AcademicPeriods->getTable()],[
                $this->AcademicPeriods->aliasField('id').' = ' . 'InstitutionExaminations.academic_period_id'
            ])
            ->where(['InstitutionExaminations.academic_period_id' =>  $academicPeriod]);
     
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        } elseif ($field == 'registration_start_date') {
            return __('Registration Start Date');
        } elseif ($field == 'registration_end_date') {
            return __('Registration End Date');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'excel_template') {
            return __('Excel Template');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
