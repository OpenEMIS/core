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

class ImportStudentGuardiansTable extends AppTable
{
    private $institutionId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', ['plugin'=>'Student', 'model'=>'StudentGuardians']);
        $this->addBehavior('Institution.ImportStudent');
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

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        if (!empty($data[$columnOrder])) {
            unset($data[$columnOrder]);
        }
    }

    public function onImportPopulateGuardianRelationsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'Relation');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Relation', $translatedCol];

        $modelData = $lookedUpTable->find('all')
            ->select([
                'name',
                'id'
            ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {

                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->id,
                ];
            }
        }

    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
        $InstitutionStudentsTable = TableRegistry::get('Institution.Students');
        $tempRow['institution_id'] = $institutionId;

        $student = $InstitutionStudentsTable->find()
            ->where([
                'institution_id' => $institutionId,
                'student_id' => $tempRow['student_id'],
            ])->first();

        if (empty($student)) {
            $rowInvalidCodeCols['student_id'] = __('Student does not exist in institution');
            return false;
        }

        if ($tempRow['student_id'] == $tempRow['guardian_id']) {
            $rowInvalidCodeCols['guardian_id'] = __('Same student and guardian id');
            return false;
        }

        $studentGuardiansTable = TableRegistry::get('Student.StudentGuardians');

        $exitsRecord = $studentGuardiansTable->find()
            ->where([
                'guardian_id' => $tempRow['guardian_id'],
                'student_id' => $tempRow['student_id']
            ])->first();

        if (!empty($exitsRecord)) {
            $rowInvalidCodeCols['guardian_id'] = __('This student and guardian has already been added.');
            return false;
        }
    }
}
