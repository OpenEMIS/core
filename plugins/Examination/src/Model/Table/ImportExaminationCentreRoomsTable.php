<?php
namespace Examination\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportExaminationCentreRoomsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Examination',
            'model' => 'ExaminationCentreRooms',
            'backUrl' => ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres']
        ]);

        // POCOR-8919
        $this->ExaminationCentres = TableRegistry::getTableLocator()->get('Examination.ExaminationCentres');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateExaminationCentresData'] = 'onImportPopulateExaminationCentresData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

// POCOR-8919

    public function onImportPopulateExaminationCentresData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
// POCOR-8919

        $selectFields = [
            $lookedUpTable->aliasField($lookupColumn),
            $lookedUpTable->aliasField('code'),
            $lookedUpTable->aliasField('name'),
// POCOR-8919
        ];

        $order = [$lookedUpTable->aliasField('name')];

        //populate exams and centre combination based on selected academic period
        $modelData = $lookedUpTable
                    ->find('all')
                    ->select($selectFields)
// POCOR-8919
            ->where([
// POCOR-8919
            ])
                    ->group([
                        $lookedUpTable->aliasField('id')
                    ])
                    ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 1;
        $data[$columnOrder]['data'][] = [__('ID'), $translatedReadableCol,
// POCOR-8919
        ];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->id,
                    $row->code . ' - ' . $row->name,
// POCOR-8919
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
// POCOR-8919

        //since academic period is pre-selected and mandatory, then we pass the academic period manually.
// POCOR-8919


        //match selected academic period with examination centres selected.
        if ($tempRow->offsetExists('examination_centre_id') && !empty($tempRow['examination_centre_id'])) {

            $ExaminationCentre = $this->ExaminationCentres
                                ->find()
                                ->where([
                                    $this->ExaminationCentres->aliasField('id') => $tempRow['examination_centre_id'],
// POCOR-8919
                                ]);

            if ($ExaminationCentre->isEmpty()) {
                $rowInvalidCodeCols['examination_centre_id'] = $this->getExcelLabel('Import', 'exam_centre_dont_match');
                return false;
            }
        }
        return true;
    }
}
