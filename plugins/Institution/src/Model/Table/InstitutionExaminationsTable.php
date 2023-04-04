<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionExaminationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
   
        $this->table('examinations');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationItemResults', ['className' => 'Examination.ExaminationItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
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
        $selectedPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
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
        $query->contain(['ExaminationItems.EducationSubjects', 'ExaminationItems.ExaminationGradingTypes']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('examination_items', [
            'type' => 'element',
            'element' => 'Examination.examination_items'
        ]);

        $this->setFieldOrder(['academic_period_id', 'code', 'name', 'description', 'education_grade_id', 'registration_start_date', 'registration_end_date', 'examination_items']);
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

        $academicPeriod = $this->request->query['academic_period_id']; 
        
            $query
            ->select(['code' => 'InstitutionExaminations.code', 'name' => 'InstitutionExaminations.name', 'grade' => 'EducationGrades.code', '	registration_start_date' => 'InstitutionExaminations.registration_start_date',  'registration_end_date' => 'InstitutionExaminations.registration_end_date', 'academic_period' => 'AcademicPeriods.name'])
            ->LeftJoin([$this->EducationGrades->alias() => $this->EducationGrades->table()],[
                $this->EducationGrades->aliasField('id').' = ' . 'InstitutionExaminations.education_grade_id'
            ])
            ->LeftJoin([$this->AcademicPeriods->alias() => $this->AcademicPeriods->table()],[
                $this->AcademicPeriods->aliasField('id').' = ' . 'InstitutionExaminations.academic_period_id'
            ])
            ->where(['InstitutionExaminations.academic_period_id' =>  $academicPeriod]);
     
    }
}
