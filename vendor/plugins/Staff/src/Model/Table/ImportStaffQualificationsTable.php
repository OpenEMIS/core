<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;
use Cake\Controller\Component;

class ImportStaffQualificationsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Staff',
            'model' => 'Qualifications'
        ]);
    }

    public function implementedEvents()
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

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $plugin = $toolbarButtons['back']['url']['plugin'];
        $controller = $toolbarButtons['back']['url']['controller'];
        if ($plugin == 'Staff') {
            $toolbarButtons['back']['url']['action'] = 'Qualifications/index';
        }
    }

    public function beforeAction($event)
    {
        $session = $this->request->session();

        if ($this->controller->name == 'Profiles') {
            $this->staffId = $session->read('Auth.User.id');
        } else if ($session->check('Staff.Staff.id')) {
            $this->staffId = $session->read('Staff.Staff.id');
        }
    }

    public function onImportPopulateQualificationTitlesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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

    public function onImportPopulateEducationFieldOfStudiesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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

    public function onImportPopulateCountriesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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

    public function onImportPopulateQualificationSpecialisationsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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

    public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
    	if (empty($this->staffId)) {
            $rowInvalidCodeCols['staff_id'] = __('No active staff');
            $tempRow['staff_id'] = false;
            return false;
        } else {
            $tempRow['staff_id'] = $this->staffId;
        }
        if (!empty($tempRow['qualification_specialisation_id'])) {
        $QualificationSpecialisations = TableRegistry::get('FieldOption.QualificationSpecialisations');
        $Specialisations = $QualificationSpecialisations
                           ->find()
                           ->where(['QualificationSpecialisations.id' => $tempRow['qualification_specialisation_id'],
                            'QualificationSpecialisations.education_field_of_study_id' => $tempRow['education_field_of_study_id']
                            ])
                            ->toArray();

        if (empty($Specialisations)) {
            $rowInvalidCodeCols['qualification_specialisation_id'] = __('Specialisation does not match for this education field of study');
            return false;
        }
    }
        return true;
    }
}
