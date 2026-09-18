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
        $controller = $toolbarButtons['back']['url']['controller'] ?? null;
        //// Log::debug('@ImportStaffQualifications::onUpdateToolbarButtons action=' . json_encode($action) . ' plugin=' . json_encode($plugin) . ' backUrl=' . json_encode($toolbarButtons['back']['url'] ?? null)); //[TEMP-LOG]
        if ($plugin == 'Staff' || $plugin == 'Student') { //POCOR-9584: handle both Staff and Student contexts (add and results)
            //POCOR-9584: use separate action + [0] keys so [1] (encoded params) stays sequential
            //            'Qualifications/index' as a single action key caused CakePHP Router to drop [1]
            $toolbarButtons['back']['url']['action'] = 'Qualifications';
            $toolbarButtons['back']['url'][0] = 'index';
        } elseif ($controller === 'Directories') {
            //POCOR-9805: reached via Directory > [person] > Staff Qualifications > Import -
            //            same class of problem as POCOR-9584 above, the back URL was resolving
            //            with no identifying pass param at all, landing Cancel on the bare
            //            Directory listing instead of this person's Staff Qualifications tab.
            //            DirectoriesController's own action for this tab is 'StaffQualifications'
            //            (not 'Qualifications' like Staff/Student), so keep it separate rather
            //            than folding into the branch above.
            $pass = $this->request->getParam('pass');
            // POCOR-9805: the 'results' page (after a successful import) has no pass[1] of its
            // own - fall back to the copy beforeAction() stashed in session while we were still
            // on 'add', where the URL did carry it.
            $encodedContext = $pass[1] ?? $this->request->getSession()->read('ImportStaffQualifications.directoryBackPass');
            if (!empty($encodedContext)) {
                //POCOR-9805: generateDirectoryBackUrl() (ImportBehavior.php) runs before this and,
                //            on the 'results' page, leaves the url with ONLY an integer key 1
                //            (key 0 is explicitly unset there). PHP arrays keep insertion order,
                //            and the router reads pass params in that order (not sorted by key) -
                //            so writing [0] then [1] below, with [1] already present, would keep
                //            [1] in its earlier position and append [0] last, silently swapping the
                //            pass params to [encoded, 'index'] instead of ['index', encoded] and
                //            404ing. Unset both first so they're always re-inserted 0 then 1.
                unset($toolbarButtons['back']['url'][0], $toolbarButtons['back']['url'][1]);
                $toolbarButtons['back']['url']['action'] = 'StaffQualifications';
                $toolbarButtons['back']['url'][0] = 'index';
                $toolbarButtons['back']['url'][1] = $encodedContext;
            }
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
        } else if ($this->controller->getName() == 'Directories') {
            //POCOR-9805: In Directory context, staff_id is directly present in the encoded
            //            pass[1] param (e.g. {"staff_id":3540,"security_user_id":3540}) - unlike
            //            here without this, $this->staffId stayed unset and every imported row
            //            would fail onImportModelSpecificValidation()'s empty-staffId check.
            $pass = $this->request->getParam('pass');
            if (!empty($pass[1])) {
                $paramsQuery = base64_decode($pass[1]);
                $jsonEndPosition = strpos($paramsQuery, '}') + 1;
                $jsonData = substr($paramsQuery, 0, $jsonEndPosition);
                $decoded = json_decode($jsonData, true);
                $this->staffId = $decoded['staff_id'] ?? null;
                // POCOR-9805: the 'results' page (shown after a successful import) has no
                // pass[1] of its own - it's a different URL reached after form submission, not
                // one carrying the person-identifying param. Remember it in session here (while
                // we're still on 'add', where the URL does carry it) so onUpdateToolbarButtons()
                // can still build a correct back-button URL once we're on 'results' too.
                $session->write('ImportStaffQualifications.directoryBackPass', $pass[1]);
            }
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
