<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTimeInterface;
use DateTime;
use PHPExcel_Worksheet;

class ImportStudentBodyMassesTable extends AppTable 
{
    private $institutionId;

    public function initialize(array $config) 
    {
        $this->table('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', ['plugin'=>'User', 'model'=>'UserBodyMasses']);
        $this->addBehavior('Institution.ImportStudent');

        // register the target table once
        $this->InstitutionStudents = TableRegistry::get('Institution.Students');
    }

    public function beforeAction($event) {
        $this->institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
    }

    public function implementedEvents() 
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }
    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $plugin = $toolbarButtons['back']['url']['plugin'];
        if ($plugin == 'Institution') {
            $toolbarButtons['back']['url']['action'] = 'Students';
        }
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) 
    {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    $date = $row->start_date;
                    $data[$columnOrder]['data'][] = [
                        $row->name,
                        $row->start_date->format('d/m/Y'),
                        $row->end_date->format('d/m/Y'),
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    }   

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        unset($data[$columnOrder]);
    }


    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) 
    {        
        // from string to dateObject
        $formattedDate = DateTime::createFromFormat('d/m/Y', $tempRow['date']);
        $tempRow['date'] = $formattedDate;
        if (empty($tempRow['date'])) {
            $rowInvalidCodeCols['date'] = __('No start date specified');
            return false;
        } else if (!$tempRow['date'] instanceof DateTimeInterface) {
            $rowInvalidCodeCols['date'] = __('Unknown date format');
            return false;
        }

        $academicPeriodLevel = $this->getAcademicPeriodLevel($tempRow['academic_period_id']);
        if (count($academicPeriodLevel)>0) {
            if ($academicPeriodLevel[0]['academic_period_level_id']!=1) { //if the level is not year
                $rowInvalidCodeCols['academic_period_id'] = __('Academic period must be in year level');
                return false;
            }
        }

        //check Student in the institution
        $studentResult = $this->InstitutionStudents->find()->where([
            'academic_period_id' => $tempRow['academic_period_id'],
            'institution_id' => $this->institutionId,
            'student_id' => $tempRow['security_user_id'],
        ])->all();

        if ($studentResult->isEmpty()) {
            $rowInvalidCodeCols['security_user_id'] = __('No such student in the institution');
            $tempRow['security_user_id'] = false;
            return false;
        }
    }
}
