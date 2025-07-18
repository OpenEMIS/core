<?php
//POCOR-9267 Starts
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use PDOException;
use Cake\Http\ServerRequest;

class MealsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_meal_students');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('SecurityUsers', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'joinType' => 'INNER']);
        
        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->getAlias()]['feature'] == 'Report.MealSummary') {
            $options['validate'] = 'MealSummary';
        }
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Education','required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, ServerRequest $request)
    {
        $options = $this->controller->getFeatureOptions($this->getAlias());
        $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($options);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
        return $attr;
    }

    public function validationMealSummary(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id')
            ->notEmpty('area_education_id');
        return $validator;
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

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $areaId = $request->getData($this->getAlias())['area_education_id'];
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, ['Report.MealSummary'])) {
                $institutionList = [];
                if (array_key_exists('institution_type_id', (array)$request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['institution_type_id'])) {
                    $institutionTypeId = $request->getData($this->getAlias())['institution_type_id'];
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
                } elseif (!$institutionTypeId && array_key_exists('area_education_id', (array)$request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['area_education_id']) && $areaId != -1) {
                    //Start:POCOR-6818 Modified this for POCOR-6859
                    $AreaT = TableRegistry::getTableLocator()->get('Area.Areas');                    
                    //Level-1
                    $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $areaId])->toArray();
                    $childArea =[];
                    $childAreaMain = [];
                    $childArea3 = [];
                    $childArea4 = [];
                    foreach($AreaData as $kkk =>$AreaData11 ){
                        $childArea[$kkk] = $AreaData11->id;
                    }
                    //level-2
                    foreach($childArea as $kyy =>$AreaDatal2 ){
                        $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
                        foreach($AreaDatas as $ky =>$AreaDatal22 ){
                            $childAreaMain[$ky] = $AreaDatal22->id;
                        }
                    }
                    //level-3
                    if(!empty($childAreaMain)){
                        foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                            $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                            foreach($AreaDatass as $ky =>$AreaDatal222 ){
                                $childArea3[$ky] = $AreaDatal222->id;
                            }
                        }
                    }
                    
                    //level-4
                    if(!empty($childAreaMain)){
                        foreach($childArea3 as $kyy =>$AreaDatal4 ){
                            $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                            foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                                $childArea4[$ky] = $AreaDatal44->id;
                            }
                        }
                    }
                    $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
                    array_push($mergeArr,$areaId);
                    $mergeArr = array_unique($mergeArr);
                    $finalIds = implode(',',$mergeArr);
                    $finalIds = explode(',',$finalIds);
                    //End:POCOR-6818 Modified this for POCOR-6859
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('area_id').' IN' => $finalIds //POCOR-6818
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

                    if (in_array($feature, ['Report.MealSummary']) && count($institutionList) > 1) {
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
            }
            return $attr;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.MealSummary'])
            )) {
                $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();

                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;

                if (in_array($feature, ['Report.Meals'])
                ) {
                    $attr['onChangeReload'] = true;
                }

                if (empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $request->getData($this->getAlias())['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.MealSummary']))) {
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order(['level'])
                        ->enableHydration(false);
                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions->toArray();
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $areaLevelId = $this->request->getData($this->getAlias())['area_level_id'];//POCOR-6333
            if (in_array($feature, ['Report.MealSummary'])) {
                    $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add') {
                        $where = [];
                        
                        if ($areaLevelId != -1 && !empty($areaLevelId)) {
                            $where[$Areas->aliasField('area_level_id')] = $areaLevelId;
                        }
                        $areas = $Areas
                            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                            ->where([$where])
                            ->order([$Areas->aliasField('order')]);
                        $areaOptions = $areas->toArray();
                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = false;
                        $attr['select'] = true;
                        /*POCOR-6333 starts*/
                        if (count($areaOptions) > 1) {
                            $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas')] + $areaOptions;
                        } else {
                            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $areaOptions;
                        }
                        /*POCOR-6333 ends*/
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['type'] = 'hidden';
                    }
            }
        }
        return $attr;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
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
            case 'education_grade_id':
                return __('Education Grade');
            case 'education_subject_id':
                return __('Education Subject');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
