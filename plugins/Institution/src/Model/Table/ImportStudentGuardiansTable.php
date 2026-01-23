<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTimeInterface;
use DateTime;
use PHPExcel_Worksheet;

class ImportStudentGuardiansTable extends AppTable
{
    private $institutionId;

    public function initialize(array $config): void // POCOR-8683
    {
        $this->table('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', ['plugin'=>'Student', 'model'=>'StudentGuardians']);
        $this->addBehavior('Institution.ImportStudent');
    }

    public function implementedEvents(): array // POCOR-8683
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

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $plugin = $toolbarButtons['back']['url']['plugin'];
        if ($plugin == 'Institution') {
            $toolbarButtons['back']['url']['action'] = 'Students';
        }
    }

    public function onGetBreadcrumb(EventInterface $event, Request $request, Component $Navigation, $persona)
    {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportPopulateUsersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        if (!empty($data[$columnOrder])) {
            unset($data[$columnOrder]);
        }
    }

    public function onImportPopulateGuardianRelationsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);

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

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
        $InstitutionStudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
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

        $studentGuardiansTable = TableRegistry::getTableLocator()->get('Student.StudentGuardians');

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
