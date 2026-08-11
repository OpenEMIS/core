<?php

namespace System\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;


class ImportLocaleContentsLanguageTable extends AppTable
{
    use OptionsTrait;

    /**
     * Holds locale_id + translation text for the current row,
     * saved after locale_contents is persisted.
     * POCOR-3673
     * @var array|null
     */
    private $importTranslationData = null;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', [
            'plugin' => false,
            'model' => 'LocaleContentsLanguage'
        ]);
        $this->addBehavior('ControllerAction.FileUpload');
        $this->LocaleContentsLanguage = TableRegistry::getTableLocator()->get('System.LocaleContentsLanguage');
        $this->Translations = TableRegistry::getTableLocator()->get('Localization.Translations');
        
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportPopulateLocalesData' => 'onImportPopulateLocalesData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if (empty($toolbarButtons['back']['url'])) {
            return;
        }
        // Back to Translations list (LocaleContentsController::LocaleContents)
        $toolbarButtons['back']['url']['action'] = 'LocaleContents';
        $toolbarButtons['back']['url'][0] = 'index';
        if (empty($toolbarButtons['back']['attr']) && !empty($attr)) {
            $toolbarButtons['back']['attr'] = $attr;
            $toolbarButtons['back']['attr']['title'] = __('Back');
        }
        if (empty($toolbarButtons['back']['type'])) {
            $toolbarButtons['back']['type'] = 'button';
        }
        if (empty($toolbarButtons['back']['label'])) {
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
        }
    }

    public function onImportPopulateLocalesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
            ->select(['id', 'iso', 'name', $lookupColumn])
            ->where(['iso !=' => 'en']);

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
            case 'label':
                return __('Label');
            case 'locale_iso':
                return __('Locale');
            case 'translated_label':
                return __('Translated Label');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * Find existing locale_contents by Label (en), or prepare a new entity.
     * ImportBehavior then saves this entity to locale_contents.
     */
    public function onImportCheckUnique(
        EventInterface $event,
        $sheet,
        $row,
        $columns,
        ArrayObject $tempRow,
        ArrayObject $importedUniqueCodes,
        ArrayObject $rowInvalidCodeCols
    ) {
        $this->importTranslationData = null;

        $columns = new Collection($columns);
        $labelCols = $columns->filter(fn($v) => $v == 'label')->toArray();

        if (empty($labelCols)) {
            $tempRow['entity'] = $this->LocaleContentsLanguage->newEntity([]);
            return;
        }

        $labelIndex = key($labelCols) + 1;
        $label = trim((string)$sheet->getCellByColumnAndRow($labelIndex, $row)->getValue());

        $localeContent = null;
        if ($label !== '') {
            $localeContent = $this->LocaleContentsLanguage->find()
                ->where(['en' => $label])
                ->first();
        }

        if ($localeContent) {
            $tempRow['entity'] = $localeContent;
        } else {
            $tempRow['entity'] = $this->LocaleContentsLanguage->newEntity([]);
        }
    }

    /**
     * Validate Excel columns, map Label -> en for locale_contents save,
     * and stash translation data for onImportUpdateUniqueKeys.
     */
    public function onImportModelSpecificValidation(
        EventInterface $event,
        $references,
        ArrayObject $tempRow,
        ArrayObject $originalRow,
        ArrayObject $rowInvalidCodeCols
    ) {
        $this->importTranslationData = null;
        $haveError = false;

        $label = trim((string)($tempRow['label'] ?? ''));
        $localeId = $tempRow['locale_iso'] ?? null;
        $translation = trim((string)($tempRow['translated_label'] ?? ''));

        if ($label === '') {
            $rowInvalidCodeCols['label'] = __('Label is required.');
            $haveError = true;
        }

        if ($localeId === null || $localeId === '') {
            $rowInvalidCodeCols['locale_iso'] = __('Locale is required.');
            $haveError = true;
        }

        if ($translation === '') {
            $rowInvalidCodeCols['translated_label'] = __('Translated Label is required.');
            $haveError = true;
        }

        if ($haveError) {
            return false;
        }

        // locale_contents.en is the English label string
        $tempRow['en'] = $label;

        $this->importTranslationData = [
            'locale_id' => (int)$localeId,
            'translation' => $translation,
        ];

        // Remove Excel-only / ImportBehavior-injected fields that are not on locale_contents
        unset(
            $tempRow['label'],
            $tempRow['locale_iso'],
            $tempRow['translated_label'],
        );

        return true;
    }

    /**
     * After locale_contents insert/update, upsert locale_content_translations.
     */
    public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity)
    {
        if (empty($this->importTranslationData) || empty($entity->id)) {
            $this->importTranslationData = null;
            return;
        }

        $localeId = $this->importTranslationData['locale_id'];
        $translationText = $this->importTranslationData['translation'];
        $this->importTranslationData = null;

        $translationEntity = $this->Translations->find()
            ->where([
                'locale_content_id' => $entity->id,
                'locale_id' => $localeId,
            ])
            ->first();

        if ($translationEntity) {
            $translationEntity->translation = $translationText;
        } else {
            $translationEntity = $this->Translations->newEntity([
                'locale_content_id' => $entity->id,
                'locale_id' => $localeId,
                'translation' => $translationText,
            ]);
        }

        if (!$this->Translations->save($translationEntity)) {
            $this->log(
                'ImportLocaleContentsLanguage: failed to save translation for locale_content_id='
                . $entity->id . ' locale_id=' . $localeId,
                'error'
            );
        }
    }


}


