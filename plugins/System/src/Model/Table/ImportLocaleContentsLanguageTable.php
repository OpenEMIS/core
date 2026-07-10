<?php

namespace System\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportLocaleContentsLanguageTable extends AppTable
{

    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', [
            'plugin' => 'System',
            'model' => 'LocaleContentsLanguage'
        ]);
        $this->addBehavior('ControllerAction.FileUpload');
        $this->LocaleContentsLanguage = TableRegistry::getTableLocator()->get('System.LocaleContentsLanguage');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportPopulateLocalesData' => 'onImportPopulateLocalesData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

     public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
          
        $toolbarButtons['back']['url'][0] = $toolbarButtons['back']['url']['action'];
        $toolbarButtons['back']['url']['action'] = 'LocaleContents/LocaleContents';
    }

    public function onImportPopulateLocalesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel); 
        $modelData = $lookedUpTable->find('all')
            ->select(['id','iso','name', $lookupColumn])
                                //->order([$lookupModel.'.order'])
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'select_file':
                return __('Select File To Import');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /*public function onImportCheckUnique(
    EventInterface $event,
    $sheet,
    $row,
    $columns,
    ArrayObject $tempRow,
    ArrayObject $importedUniqueCodes,
    ArrayObject $rowInvalidCodeCols)
    {
        $tempRow['columns'] = $columns; // POCOR-8683 start
        $columns = new Collection($columns);
        $extractedlabel = $columns->filter(fn($v) => $v === 'label');

        $labelNoIndex   = key($extractedlabel->toArray()) + 1;

        $label          = $sheet->getCellByColumnAndRow($labelNoIndex, $row)->getValue();
        $label          = is_string($label) ? trim($label) : $label;
        $localeContentsLanguage = TableRegistry::getTableLocator()->get('System.LocaleContentsLanguage');

        $entity = $localeContentsLanguage->find()
            ->where([
                'en' => $label,
            ])
            ->enableHydration(false)
            ->first();

        if ($entity) {
            $tempRow['entity'] = $entity;    // Update existing record
        } else {
            $data['en'] = $label;
            $tempRow['entity'] = $localeContentsLanguage->newEntity($data); // Insert new record
        }
    }*/

    public function onImportCheckUnique(
    EventInterface $event,
    $sheet,
    $row,
    $columns,
    ArrayObject $tempRow,
    ArrayObject $importedUniqueCodes,
    ArrayObject $rowInvalidCodeCols
) {

    $columns = new Collection($columns);

    $labelIndex = key($columns->filter(fn($v) => $v == 'label')->toArray()) + 1;
    $localeIndex = key($columns->filter(fn($v) => $v == 'locale_id')->toArray()) + 1;

    $label = trim((string)$sheet->getCellByColumnAndRow($labelIndex, $row)->getValue());
    $localeId = $sheet->getCellByColumnAndRow($localeIndex, $row)->getValue();

    $LocaleContents = TableRegistry::getTableLocator()->get('System.LocaleContentsLanguage');
    $LocaleContentTranslations = TableRegistry::getTableLocator()->get('Localization.Translations');

    $localeContent = $LocaleContents->find()
        ->where(['en' => $label])
        ->first();

    $tempRow['localeContent'] = $localeContent;
    $tempRow['label'] = $label;

    if ($localeContent) {

        $translation = $LocaleContentTranslations->find()
            ->where([
                'locale_content_id' => $localeContent->id,
                'locale_id' => $localeId
            ])
            ->first();

        if ($translation) {
            $tempRow['entity'] = $translation;
        } else {
            $tempRow['entity'] = $LocaleContentTranslations->newEntity([]);
        }

    } else {

        $tempRow['entity'] = $LocaleContentTranslations->newEntity([]);

    }
}

    public function onImportModelSpecificValidation(
    EventInterface $event,
    $references,
    $tempRow,
    ArrayObject $originalRow,
    ArrayObject $rowInvalidCodeCols
    ) {
        $LocaleContents = TableRegistry::getTableLocator()->get('System.LocaleContentsLanguage');

        $label = trim($tempRow['label'] ?? '');
        $localeId = $tempRow['locale_id'] ?? null;
        $translation = $tempRow['translation'] ?? '';

        if ($label === '') {
            $rowInvalidCodeCols['label'][] = __('Label is required.');
            return false;
        }

        if (empty($localeId)) {
            $rowInvalidCodeCols['locale_id'][] = __('Locale is required.');
            return false;
        }

        // Find existing locale content
        $localeContent = $LocaleContents->find()
            ->where(['en' => $label])
            ->first();

        // Create if not exists
        if (!$localeContent) {
            $localeContent = $LocaleContents->newEntity([
                'en' => $label
            ]);

            if (!$LocaleContents->save($localeContent)) {
                $rowInvalidCodeCols['label'][] = __('Unable to save label.');
                return false;
            }
        }

        // Pass the FK to locale_content_translations
        $tempRow['locale_content_id'] = $localeContent->id;

        // Prepare translation entity
        $tempRow['entity']->locale_content_id = $localeContent->id;
        $tempRow['entity']->locale_id = $localeId;
        $tempRow['entity']->translation = $translation;

        return true;
    }


}






