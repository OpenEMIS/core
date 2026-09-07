<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;

class ImportHousesTable extends AppTable
{
    private $institutionId;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'InstitutionAssociations',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Associations']
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateAcademicPeriodsData'] = 'onImportPopulateAcademicPeriodsData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        // POCOR-7692: without this, beforeAction() below is never invoked by processAction()
        // (it dispatches through the model's own EventManager, which only calls registered
        // listeners), so $this->institutionId stays null and every imported row is rejected
        // with "No active institution".
        $events['ControllerAction.Model.beforeAction'] = 'beforeAction';
        return $events;
    }

    public function beforeAction($event)
    {
        $institutionId = $this->getQueryString('institution_id');
        if (!is_null($institutionId)) {
            $this->institutionId = $institutionId;
        }
    }

    public function onImportPopulateAcademicPeriodsData(
        EventInterface $event,
        $lookupPlugin,
        $lookupModel,
        $lookupColumn,
        $translatedCol,
        ArrayObject $data,
        $columnOrder
    ) {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];

        if (!empty($modelData)) {
            foreach ($modelData as $row) {
                if ($row->academic_period_level_id == 1) {
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

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }
        $tempRow['institution_id'] = $this->institutionId;

        return true;
    }
}
