<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use PHPExcel_Worksheet;

use App\Model\Table\AppTable;

class ImportAssessmentItemResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'AssessmentItemResults',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'AssessmentItemResults']
        ]);

        // register table once
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->notEmpty(['education_grade', 'select_file']);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $request = $this->request;
        if (empty($request->query('education_grade'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['education_grade']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->dependency = [];
        $this->ControllerAction->field('education_grade', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => true]);
        $this->ControllerAction->setFieldOrder(['education_grade', 'select_file']);    
    }

    public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {    
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

            $allowedEducationGradeList = $this->InstitutionGrades
                ->find('list', [
                    'keyField' => 'education_grade_id',
                    'valueField' => 'EducationGrades'
                ])
                ->leftJoin(['EducationGrades' => 'education_grades'], [
                            'EducationGrades.id = ' . $this->InstitutionGrades->aliasField('education_grade_id')
                ])
                ->select(['EducationGrades' => 'EducationGrades.name', 'education_grade_id' => 'EducationGrades.id'])
                ->where([$this->InstitutionGrades->aliasField('institution_id') => $institutionId])
                ->group([
                    'EducationGrades.id',
                ])
                ->toArray();

                $attr['options'] = $allowedEducationGradeList;
                // useing onChangeReload to do visible
                $attr['onChangeReload'] = 'changeEducationGrade';
        }
        return $attr;
    }

    public function onImportPopulateAssessmentPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
            ->select([
                'code',
                'name'
            ]);
        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [
            
            $columnHeader,
            $nameHeader,
        ];
       
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {

                $data[$columnOrder]['data'][] = [
                    $row->{$lookupColumn},
                    $row->name
                ];
            }
        }
    }

    public function onImportPopulateClassData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $academicPeriod = $this->request->query['academic_period_id'];
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $Institution = TableRegistry::get('Institution.Institutions');
        $InstitutionClassesResult = $InstitutionClasses->find()
                                    ->select(['id', 'name'])
                                    ->leftJoin([$Institution->alias() => $Institution->table()],
                                        [
                                        $InstitutionClasses->aliasField('institution_id = ') . $Institution->aliasField('id')
                                    ])
                                    ->where([
                                        $InstitutionClasses->aliasField('academic_period_id') => $academicPeriod ,
                                        $InstitutionClasses->aliasField('institution_id') => $institutionId
                                    ]); 

        $translatedReadableCol = $this->getExcelLabel($InstitutionClassesResult, 'Id');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $InstitutionClassesResult->find('all')
            ->select([
                'id',
                'name'
            ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {

                $data[$columnOrder]['data'][] = [
                    $row->id,
                    $row->name,
                ];
            }
        }

    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
            ->select([
                'id',
                'openemis_no'
            ]);
        $nameHeader = $this->getExcelLabel($lookedUpTable, 'id');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [
            
            $columnHeader,
            $nameHeader,
        ];
       
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {

                $data[$columnOrder]['data'][] = [
                    $row->{$lookupColumn},
                    $row->id
                ];
            }
            //echo "<pre>";print_r($data);die("Shiva");
        }
    }
}    