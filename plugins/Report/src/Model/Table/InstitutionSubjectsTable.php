<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class InstitutionSubjectsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_subjects');
		parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        
	$this->addBehavior('Excel', [
            'autoFields' => false
        ]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
        }
        
        if (!empty($requestData->education_subject_id)) {
            $conditions[$this->aliasField('education_subject_id')] = $requestData->education_subject_id;
        }
        
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $Staff = TableRegistry::get('User.Users');

        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => $query->func()->concat(['Institutions.code' => 'literal', ' - ', 'Institutions.name' => 'literal']),
                'area_code' => 'Areas.code',
                'area_name' => $query->func()->concat(['Areas.code' => 'literal', ' - ', 'Areas.name' => 'literal']),
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'EducationGrades.name',
                'class_name' => 'InstitutionClasses.name',
                'AcademicPeriods.name',
                'staff_id' => 'InstitutionSubjectStaff.staff_id',
                'staff_name' => $query->func()->concat(['Users.openemis_no' => 'literal', ' - ', 'Users.first_name' => 'literal', ' ', 'Users.last_name' => 'literal']),
                'total_students' => $query
                    ->newExpr()
                    ->add($this->aliasField('total_male_students'))
                    ->add($this->aliasField('total_female_students'))
                    ->tieWith('+'),
                $this->aliasField('name'),
                $this->aliasField('no_of_seats'),
                $this->aliasField('total_male_students'),
                $this->aliasField('total_female_students'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('academic_period_id'),
            ])
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'EducationGrades',
                'EducationSubjects',
                'AcademicPeriods'
            ])
            ->leftJoin([$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()], [
                $this->aliasField('id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
            ])
            ->leftJoin([$InstitutionClasses->alias() => $InstitutionClasses->table()], [
                $InstitutionClassSubjects->aliasField('institution_class_id =') . $InstitutionClasses->aliasField('id')
            ])
            ->leftJoin([$InstitutionSubjectStaff->alias() => $InstitutionSubjectStaff->table()], [
                $InstitutionSubjectStaff->aliasField('institution_subject_id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
            ])
            ->leftJoin([$Staff->alias() => $Staff->table()], [
                $Staff->aliasField('id =') . $InstitutionSubjectStaff->aliasField('staff_id')
            ])
            ->where($conditions);            
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
            $attr['options'] = $this->controller->getFeatureOptions('Institutions');
            return $attr;
    }
        

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {   
        foreach ($fields as $key => $value) {
            if ($value['field'] == 'education_subject_id') {
                $fields[$key] = array('key' => 'InstitutionClasses.name',
                    'field' => 'class_name',
                    'type' => 'string',
                    'label' => __('Institution Class'));
            }
        }
        
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        
        foreach ($cloneFields as $key => $value) {
            
            if (in_array($value['field'], ['academic_period_id'])) {
                    unset($cloneFields[$key]);
                    break;
            }
            
            if ($value['field'] == 'class_name') {
                $newFields[] = [
                    'key' => 'institution_name',
                    'field' => 'institution_name',
                    'type' => 'string',
                    'label' => 'Institution'
                ];
                
                $newFields[] = [
                    'key' => 'area_name',
                    'field' => 'area_name',
                    'type' => 'string',
                    'label' => __('Area Education')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionClasses.name',
                    'field' => 'class_name',
                    'type' => 'string',
                    'label' => __('Institution Class')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.name',
                    'field' => 'name',
                    'type' => 'string',
                    'label' => __('Subject Name')
                ];
                
                $newFields[] = [
                    'key' => 'staff_name',
                    'field' => 'staff_name',
                    'type' => 'string',
                    'label' => __('Subject Teacher')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.no_of_seats',
                    'field' => 'no_of_seats',
                    'type' => 'integer',
                    'label' => __('Number of seats')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_male_students',
                    'field' => 'total_male_students',
                    'type' => 'integer',
                    'label' => __('Number of Male students')
                ];
                
                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_female_students',
                    'field' => 'total_female_students',
                    'type' => 'integer',
                    'label' => __('Number of Female students')
                ];
                
                $newFields[] = [
                    'key' => 'total_students',
                    'field' => 'total_students',
                    'type' => 'integer',
                    'label' => __('Total number of students')
                ];

            }
            //$newFields[] = $value;
        }
        
        $fields->exchangeArray($newFields); 
    }
}
