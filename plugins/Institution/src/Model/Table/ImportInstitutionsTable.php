<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportInstitutionsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import');

        // register the target table once
        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $this->addBehavior('ControllerAction.FileUpload');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportGetClassificationId' => 'onImportGetClassificationId',
            'Model.import.onImportPopulateAreasData' => 'onImportPopulateAreasData',
            'Model.import.onImportPopulateAreaAdministrativesData' => 'onImportPopulateAreaAdministrativesData',
            'Model.import.onImportPopulateClassificationData' => 'onImportPopulateClassificationData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $toolbarButtons['back']['url'][0] = $toolbarButtons['back']['url']['action'];
        $toolbarButtons['back']['url']['action'] = 'Institutions';
    }

    public function onImportCheckUnique(EventInterface $event, $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols)
    {
        $columns = new Collection($columns);
        $filtered = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'code';
        });
        $codeIndex = key($filtered->toArray());
        $code = $sheet->getCellByColumnAndRow($codeIndex, $row)->getValue();

        if (in_array($code, $importedUniqueCodes->getArrayCopy())) {
            $rowInvalidCodeCols['code'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
            return false;
        }
        // POCOR-8683 start
        $institution = null;
        if ($code) {
            $institution = $this->Institutions->find()->where(['code' => $code])->first();
        }
        // POCOR-8683 end
        if (!$institution) {
            $tempRow['entity'] = $this->Institutions->newEmptyEntity();
        } else {
            $tempRow['entity'] = $institution;
        }
    }

    public function onImportGetClassificationId(EventInterface $event, $cellValue)
    {
        $options = $this->getSelectOptions('Institutions.classifications');
        foreach ($options as $key => $value) {
            if ($cellValue == $key) {
                return $cellValue;
            }
        }
        return null;
    }

    public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity)
    {
        $importedUniqueCodes[] = $entity->code;
    }

    public function onImportPopulateAreasData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.area_level_id', $lookupModel.'.order'];

        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
                                ->select($selectFields)
                                ->order($order)
                                ;

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

    public function onImportPopulateAreaAdministrativesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.area_administrative_level_id', $lookupModel.'.order'];

        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
                                ->select($selectFields)
                                ->order($order)
                                ;

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

    public function onImportPopulateClassificationData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('Classification', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        $options = $this->getSelectOptions('Institutions.classifications');
        foreach ($options as $key => $value) {
            $data[$columnOrder]['data'][] = [
                $value,
                $key
            ];
        }
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        return true;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'select_file':
                return __('Select File To Import');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
