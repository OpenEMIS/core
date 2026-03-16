<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;
use Cake\Controller\Component;

class ImportStaffQualificationsTable extends AppTable
{
    public function initialize(array $config):void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Staff',
            'model' => 'Qualifications'
        ]);
    }

    public function implementedEvents():array
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Model.import.onImportPopulateQualificationTitlesData'] = 'onImportPopulateQualificationTitlesData';
        $events['Model.import.onImportPopulateEducationFieldOfStudiesData'] = 'onImportPopulateEducationFieldOfStudiesData';
        $events['Model.import.onImportPopulateCountriesData'] = 'onImportPopulateCountriesData';
        $events['Model.import.onImportPopulateQualificationSpecialisationsData'] = 'onImportPopulateQualificationSpecialisationsData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        //POCOR-9584: start - null guard; always redirect back to Qualifications/index for Staff and Student contexts
        if (empty($toolbarButtons['back']['url'])) {
            return;
        }
        $plugin = $toolbarButtons['back']['url']['plugin'] ?? null;
        //// Log::debug('@ImportStaffQualifications::onUpdateToolbarButtons action=' . json_encode($action) . ' plugin=' . json_encode($plugin) . ' backUrl=' . json_encode($toolbarButtons['back']['url'] ?? null)); //[TEMP-LOG]
        if ($plugin == 'Staff' || $plugin == 'Student') { //POCOR-9584: handle both Staff and Student contexts (add and results)
            //POCOR-9584: use separate action + [0] keys so [1] (encoded params) stays sequential
            //            'Qualifications/index' as a single action key caused CakePHP Router to drop [1]
            $toolbarButtons['back']['url']['action'] = 'Qualifications';
            $toolbarButtons['back']['url'][0] = 'index';
        }
        //POCOR-9584: end
        //// Log::debug('@ImportStaffQualifications::onUpdateToolbarButtons result backUrl=' . json_encode($toolbarButtons['back']['url'] ?? null)); //[TEMP-LOG]
    }

    public function beforeAction($event)
    {
        $session = $this->request->getSession();
        //// Log::debug('@ImportStaffQualifications::beforeAction controller=' . $this->controller->getName()); //[TEMP-LOG]

        if ($this->controller->getName() == 'Profiles') {
            $this->staffId = $session->read('Auth.User.id');
            //// Log::debug('@ImportStaffQualifications::beforeAction from Profiles, staffId=' . json_encode($this->staffId)); //[TEMP-LOG]
        } else if ($this->controller->getName() == 'Students') {
            //POCOR-9584: start - In Students context, student_id is in encoded params (pass[1])
            $pass = $this->request->getParam('pass');
            if (!empty($pass[1])) {
                $paramsQuery = base64_decode($pass[1]);
                $jsonEndPosition = strpos($paramsQuery, '}') + 1;
                $jsonData = substr($paramsQuery, 0, $jsonEndPosition);
                $decoded = json_decode($jsonData, true);
                $this->staffId = $decoded['student_id'] ?? null;
            }
            //POCOR-9584: end
            //// Log::debug('@ImportStaffQualifications::beforeAction from Students, staffId=' . json_encode($this->staffId)); //[TEMP-LOG]
        } else if ($session->check('Staff.Staff.id')) {
            $this->staffId = $session->read('Staff.Staff.id');
            //// Log::debug('@ImportStaffQualifications::beforeAction from Staff session, staffId=' . json_encode($this->staffId)); //[TEMP-LOG]
        }
        //// Log::debug('@ImportStaffQualifications::beforeAction result staffId=' . json_encode($this->staffId)); //[TEMP-LOG]
    }

    public function onImportPopulateQualificationTitlesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
                                ->select(['name', $lookupColumn])
                                ->order($lookupModel.'.order');

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateEducationFieldOfStudiesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
                                ->select(['name', $lookupColumn])
                                ->order($lookupModel.'.order');

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateCountriesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
                                ->select(['name', $lookupColumn])
                                ->order($lookupModel.'.order');

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateQualificationSpecialisationsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
                                   ->select('EducationFieldOfStudies.name')
                                   ->select($lookedUpTable)
                                   ->join([
                                    'EducationFieldOfStudies' => [
                                    'table' => 'education_field_of_studies',
                                    'conditions' => [
                                        'EducationFieldOfStudies.id = '.$lookedUpTable->aliasField('education_field_of_study_id')
                                            ]
                                        ]
                                    ])
                                    ->order($lookupModel.'.order');

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'Specialisations');
        $translatedReadableColData = $this->getExcelLabel($lookedUpTable, 'Education Field Of Study');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol, $translatedReadableColData];
        
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn},
                    $row->EducationFieldOfStudies['name']
                ];
            }
        }
    }

    public function onImportPopulateEducationSubjectsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
                                    ->order($lookupModel.'.order');

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'Name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        // Log::debug('@ImportStaffQualifications::onImportModelSpecificValidation staffId=' . json_encode($this->staffId)); //[TEMP-LOG]
    	if (empty($this->staffId)) {
            // Log::debug('@ImportStaffQualifications::onImportModelSpecificValidation staffId is empty, returning false'); //[TEMP-LOG]
            $rowInvalidCodeCols['staff_id'] = __('No active staff');
            $tempRow['staff_id'] = false;
            return false;
        } else {
            $tempRow['staff_id'] = $this->staffId;
            // Log::debug('@ImportStaffQualifications::onImportModelSpecificValidation staffId set in tempRow=' . json_encode($this->staffId)); //[TEMP-LOG]
        }
        if (!empty($tempRow['qualification_specialisation_id'])) {
            // Log::debug('@ImportStaffQualifications::onImportModelSpecificValidation checking specialisation_id=' . json_encode($tempRow['qualification_specialisation_id']) . ', field_id=' . json_encode($tempRow['education_field_of_study_id'])); //[TEMP-LOG]
        $QualificationSpecialisations = TableRegistry::getTableLocator()->get('FieldOption.QualificationSpecialisations');
        $Specialisations = $QualificationSpecialisations
                           ->find()
                           ->where(['QualificationSpecialisations.id' => $tempRow['qualification_specialisation_id'],
                            'QualificationSpecialisations.education_field_of_study_id' => $tempRow['education_field_of_study_id']
                            ])
                            ->toArray();

        if (empty($Specialisations)) {
            // Log::debug('@ImportStaffQualifications::onImportModelSpecificValidation specialisation check failed'); //[TEMP-LOG]
            $rowInvalidCodeCols['qualification_specialisation_id'] = __('Specialisation does not match for this education field of study');
            return false;
        }
        // Log::debug('@ImportStaffQualifications::onImportModelSpecificValidation specialisation check passed'); //[TEMP-LOG]
    }
        // Log::debug('@ImportStaffQualifications::onImportModelSpecificValidation returning true'); //[TEMP-LOG]
        return true;
    }
}
