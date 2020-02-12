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
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');

        $query
            ->select([
                $this->aliasField('name'),
                $this->aliasField('no_of_seats'),
                $this->aliasField('total_male_students'),
                $this->aliasField('total_female_students'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('academic_period_id'),
                'institution_code' => 'Institutions.code',
                'Institutions.name',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'EducationGrades.name',
                'class_name' => 'InstitutionClasses.name',
                'AcademicPeriods.name',
                
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
            
            $newFields[] = $value;
            if ($value['field'] == 'institution_id') {
                $newFields[] = [
                    'key' => 'Institutions.code',
                    'field' => 'institution_code',
                    'type' => 'string',
                    'label' => ''
                ];

                $newFields[] = [
                    'key' => 'Institutions.area_code',
                    'field' => 'area_code',
                    'type' => 'string',
                    'label' => __('Area Education Code')
                ];

                $newFields[] = [
                    'key' => 'Institutions.area',
                    'field' => 'area_name',
                    'type' => 'string',
                    'label' => __('Area Education')
                ];

                $newFields[] = [
                    'key' => 'AreaAdministratives.code',
                    'field' => 'area_administrative_code',
                    'type' => 'string',
                    'label' => __('Area Administrative Code')
                ];

                $newFields[] = [
                    'key' => 'AreaAdministratives.name',
                    'field' => 'area_administrative_name',
                    'type' => 'string',
                    'label' => __('Area Administrative')
                ];

                
            }
        }
        
        $fields->exchangeArray($newFields); 
    }
}
