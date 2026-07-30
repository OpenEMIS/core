<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;

class TextbooksTable extends AppTable  {

    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config): void {
        parent::initialize($config);

        $this->belongsTo('Textbooks',           ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades',     ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects',   ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Staff.Staff',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('academic_period_id', ['select' => false]);
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            //POCOR-9743
            if (!isset($this->request->getData($this->getAlias())['feature'])) {
                $selectedFeature = $this->getSelectedFeature($attr['entity'] ?? null);
                if ($selectedFeature === null) {
                    $options = $attr['options'];
                    reset($options);
                    $selectedFeature = key($options);
                }
                $this->request = $this->request->withData($this->getAlias() . '.feature', $selectedFeature);
            }
        }

        return $attr;
    }

    /**
     * Resolve selected report feature from request data or entity.
     */
    private function getSelectedFeature(?Entity $entity = null): ?string
    {
        $requestData = $this->request->getData($this->getAlias());
        if (!empty($requestData['feature'])) {
            return $requestData['feature'];
        }

        if ($entity !== null && $entity->has('feature') && !empty($entity->feature)) {
            return $entity->feature;
        }

        return null;
    }
    function array_flatten($array) {
        if (!is_array($array)) {
            return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result = array_merge($result, array($key => $value));
            }
        }
        return $result;
    }
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = $this->AcademicPeriods->getYearList();
        $attr['default'] = $this->AcademicPeriods->getCurrent();
        return $attr;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        if ($academicPeriodId!=0) {
            $query->where([
                $this->aliasField('academic_period_id') => $academicPeriodId
            ]);
        }
    }
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        return $events;
    }
    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $feature = $this->getSelectedFeature($attr['entity'] ?? null);
        if ($feature && in_array($feature, ['Report.InstitutionTextbooks'])) {
            if ($action == 'add') {
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
                $areaOptions = $Areas
                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                    ->order([$Areas->aliasField('level')]);

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions->toArray();
                $attr['onChangeReload'] = true;
            } else {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldAreaId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $feature = $this->getSelectedFeature($attr['entity'] ?? null);
        if ($feature && in_array($feature, ['Report.InstitutionTextbooks'])) {
            if ($action == 'add') {
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                $areaOptions = $Areas
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->order([$Areas->aliasField('order')]);

                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = ['' => '-- ' . __('Select') . ' --', '0' => __('All Areas')] + $areaOptions->toArray();
                $attr['onChangeReload'] = true;
            } else {
                $attr['type'] = 'hidden';
            }
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $requestData = $request->getData($this->getAlias()) ?? [];
        $areaId = $requestData['area_id'] ?? null;
        $institutionTypeId = $requestData['institution_type_id'] ?? null;
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $feature = $this->getSelectedFeature($attr['entity'] ?? null);

        if ($feature && in_array($feature, ['Report.InstitutionTextbooks'])) {
                $institutionList = [];
                if (array_key_exists('institution_type_id', $requestData) && !empty($requestData['institution_type_id'])) {
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('institution_type_id') => $institutionTypeId
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);


                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                } elseif (!$institutionTypeId && array_key_exists('area_id', $requestData) && !empty($requestData['area_id']) && $areaId != -1) {
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('area_id') => $areaId
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                } else {
                    $institutionQuery = $InstitutionsTable
                                       ->find('list', [
                                                'keyField' => 'id',
                                                'valueField' => 'code_name'
                                            ])
                                       ->order([
                                           $InstitutionsTable->aliasField('code') => 'ASC',
                                           $InstitutionsTable->aliasField('name') => 'ASC'
                                       ]);

                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                }

                if (empty($institutionList)) {
                    $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                } else {

                    if (in_array($feature, [
                        'Report.InstitutionTextbooks'
                    ]) && count($institutionList) > 1) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                    } else {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }

                return $attr;
        }

        return $attr;
    }

    /*POCOR-6176 Starts function for ordering required order of fields*/
    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        $feature = $this->getSelectedFeature($entity);
        if (!$feature) {
            return;
        }

        $fieldsOrder = ['feature'];
        switch ($feature) {
            case 'Report.InstitutionTextbooks':
                $fieldsOrder[] = 'academic_period_id';
                $fieldsOrder[] = 'area_level_id';
                $fieldsOrder[] = 'area_id';
                $fieldsOrder[] = 'institution_id';
                $fieldsOrder[] = 'format';
                $this->ControllerAction->field('area_id', [
                    'select' => false,
                    'attr' => ['label' => __('Area Education')],
                ]);
                break;
            default:
                break;
        }
        $this->ControllerAction->setFieldOrder($fieldsOrder);
    }
    /*POCOR-6176 Ends*/
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'area_level_id':
                return __('Area Level');
            case 'institution_id':
                return __('Institution');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}