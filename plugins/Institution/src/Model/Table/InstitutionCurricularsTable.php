<?php

namespace Institution\Model\Table;

use ArrayObject;
use stdClass;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Session;

//POCOR-6673
class InstitutionCurricularsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('CurricularTypes', ['className' => 'FieldOption.CurricularTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->hasMany('InstitutionCurricularStaff', ['className' => 'Institution.InstitutionCurricularStaff', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Excel', ['pages' => ['index', 'view']]);

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InstitutionCurriculars' =>['id']
            ]
        ]);

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $sortable = !is_null($this->request->getQuery('sort')) ? true : false;
        $institutionId = $this->getInstitutionID();
        $curricularStudent = TableRegistry::get('Institution.InstitutionCurricularStudents');
        $users = TableRegistry::get('User.Users');

        $query->select([
            'id',
            'name',
            'total_male_students',
            'total_female_students',
            'institution_id',
            'modified_user_id',
            'modified',
            'created_user_id',
            'created',
        ])
            ->where(
                [
                    $this->aliasField('institution_id') => $institutionId
                ])
            ->group([$this->aliasField('id')]);

        if (!$sortable) {
            $query
                ->order([
                    $this->aliasField('name') => 'ASC'
                ]);
        }
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->getQuery();
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('total_male_students', ['visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('total_female_students', ['visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('total_students', ['visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('curricular_type_id', ['visible' => ['index' => false]]);
        $this->field('category', ['visible' => ['index' => false]]);
        $this->field('staff_id', ['visible' => ['index' => false]]);
        $this->setFieldOrder([
            'name', 'staff_id', 'category', 'total_male_students', 'total_female_students', 'total_students'
        ]);

    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('total_male_students', ['visible' => false]);
        $this->field('total_female_students', ['visible' => false]);
        $this->field('curricular_type_id', ['type' => 'select']);
        $this->field('category', ['type' => 'select']);
        $this->field('staff_id', ['type' => 'select', 'visible' => false]);
        $this->setFieldOrder([
            'name',
            'category',
            'curricular_type_id']);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('total_male_students', ['visible' => false]);
        $this->field('total_female_students', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => ['index'=>false]]);
        $this->field('curricular_type_id', ['type' => 'select']);
        $this->field('category', ['type' => 'select']);
        // $this->field('staff_id', ['type' => 'select']);
        $this->setFieldOrder([
            'name',
            'category',
            'curricular_type_id']);
        // $entity->institution_curricular_id = $_SESSION['curricularId'];
        $paramPass = $this->request->getParam('pass');
        $ids = !is_null($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : 0;
        $curricularId = $ids['id'];
        $curricularStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStaff');
        $getStaff = $curricularStaff->find()->select(['staff_id'])
            ->where([$curricularStaff->aliasField('institution_curricular_id') => $curricularId]);
        $staff = [];
        if (!empty($getStaff)) {
            foreach ($getStaff as $value) {
                $staff[] = $value->staff_id;
            }
        }

        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $UserData = TableRegistry::get('User.Users');
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        $this->InstitutionCurriculars = TableRegistry::getTableLocator()->get('Institution.InstitutionCurriculars');
        $join = [];
        $join[''] = [
            'type' => 'inner',
            'table' => "(SELECT institution_staff.staff_id user_id
                        FROM institution_staff
                        WHERE institution_staff.institution_id = $institutionId
                        AND institution_staff.staff_status_id = 1
                        GROUP BY institution_staff.staff_id
                            ) subq",
            'conditions' => ['subq.user_id = Users.id'],
        ];
        $requestorOptions = $UserData
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->select([
                $UserData->aliasField('id'),
                $UserData->aliasField('openemis_no'),
                $UserData->aliasField('first_name'),
                $UserData->aliasField('middle_name'),
                $UserData->aliasField('third_name'),
                $UserData->aliasField('last_name')
            ])->order([$UserData->aliasField('first_name'),]);

        $data = $requestorOptions->join($join)->toArray();

        $this->field('staff_id', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Staff')
            ]
        ]);
        $this->fields['staff_id']['options'] = $data;

    }

    public function onUpdateFieldCategory(Event $event, array $attr, $action, ServerRequest $request)
    {
        $categories = array(1 => 'Co-Curricular', 0 => 'Extracurricular'); //POCOR-7751
        $entity = $attr['entity'];
        if ($action == 'add') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Category') . ' --'] + $categories;
            $attr['onChangeReload'] = 'changeStatus';
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Category') . ' --'] + $categories;
            $attr['onChangeReload'] = 'changeStatus';
        }
        return $attr;
    }

    public function onUpdateFieldCurricularTypeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $categoryId = $this->request->getData()[$this->getAlias()]['category'];
        if($categoryId == null){
            $categoryId = $categoryData ? 0 : 1;
        }
        $CurricularTypes = TableRegistry::get('FieldOption.CurricularTypes');
        $this->InstitutionCurriculars = TableRegistry::get('Institution.InstitutionCurriculars');
        $getCurricularsType = $CurricularTypes->find('list')->where(['category'=>$categoryId])->toArray();
        if ($action == 'add') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Type') . ' --'] + $getCurricularsType;
            $attr['onChangeReload'] = false;
        } elseif($action == 'edit'){
            $paramPass = $this->request->getParam('pass');
            $ids = !is_null($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : 0;
            $curricularId = $ids['id'];
            // $entity->institution_curricular_id =  $curricularId;
            // $curriculardecode = $entity->institution_curricular_id;
            $curriculardecode = $curricularId;
            $tyepId = $this->InstitutionCurriculars->get($curriculardecode)->curricular_type_id;
            $CurricularTypesName = $CurricularTypes->find('list')->where(['id'=>$tyepId])->first();
            $attr['type'] = 'readonly';
            $attr['value'] = $typeId;
            $attr['attr']['value'] = $CurricularTypesName;
        }
        return $attr;
    }


    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
//        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $curricularId = $_SESSION['curricularId'];
        $curricularStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStaff');
        $currentTimeZone = date("Y-m-d H:i:s");
        if (!empty($entity->staff_id['_ids'])) {
            $StaffIds = $entity->staff_id['_ids'];
            $checkCurricularStaff = $curricularStaff->find()->where(['institution_curricular_id' => $curricularId])->toArray();
            if (!empty($checkCurricularStaff)) {
                $deleteStaff = $curricularStaff->deleteAll(['institution_curricular_id' => $curricularId]);
            }
            foreach ($StaffIds as $staffId) {
                $CurricularStaff = $curricularStaff->find()->where(['staff_id' => $staffId, 'institution_curricular_id' => $curricularId])->first();

                if (empty($CurricularStaff)) {
                    $data = [
                        'id' => Text::uuid(),
                        'staff_id' => $staffId,
                        'institution_curricular_id' => $curricularId,
                        'created_user_id' => 1,
                        'created' => $currentTimeZone,
                        'modified_user_id' => 1,
                        'modified' => $currentTimeZone,
                    ];
                    $entity = $curricularStaff->newEntity($data);
                    $save = $curricularStaff->save($entity);
                }
            }
        }
    }


    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $tabElements = $this->controller->getCurricularsTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'InstitutionCurriculars');

        $this->field('staff_id', ['visible' => true]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $paramPass = $this->request->getParam('pass');
        $ids = isset($paramPass[1]['id']) ? $this->paramsDecode($paramPass[1]['id']) : 0;
        $_SESSION["curricularId"] = $curricularId;

    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {

        $this->field('curricular_type_id', ['type' => 'select', 'entity' => $entity]);
    }

    public function onGetCategory(Event $event, Entity $entity)
    {

        return $entity->category ? __('Co-Curricular') : __('Extracurricular');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {

        if ($field == 'total_male_students') {
            return __('Male Students');
        } else if ($field == 'total_female_students') {
            return __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        $curricularId = $entity->id;
        $curricularStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStaff');
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $data = $curricularStaff->find()->select(['openemis_no' => $users->aliasField('openemis_no'), 'first_name' => $users->aliasField('first_name'), 'middle_name' => $users->aliasField('middle_name'), 'third_name' => $users->aliasField('third_name'), 'last_name' => $users->aliasField('last_name')])
            ->leftJoin([$users->getAlias() => $users->getTable()],
                [$users->aliasField('id') . ' = ' . $curricularStaff->aliasField('staff_id')
                ])
            ->where([$curricularStaff->aliasField('institution_curricular_id') => $curricularId])->toArray();
        $staff = [];
        foreach ($data as $value) {
            $staff[] = $value->openemis_no . ' - ' . $value->first_name . ' ' . $value->middle_name . ' ' . $value->third_name . ' ' . $value->last_name;
        }
        return implode(', ', $staff);
    }

    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        // $curricularId = $_SESSION['curricularId'];
        $queryString = $this->request->getData('primaryKey');
        $curricularId = $this->paramsDecode($queryString)['id'];
        $curricularStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStaff');
        $curricularStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStudents');
        $checkStudent = $curricularStudent->find()->where([$curricularStudent->aliasField('institution_curricular_id') => $curricularId])->first();
        $checkStaff = $curricularStaff->find()->where([$curricularStaff->aliasField('institution_curricular_id') => $curricularId])->first();
        if (!empty($checkStudent) || !empty($checkStaff)) {
            $message = __('Its Associated with Other Data');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function onGetTotalStudents(Event $event, Entity $entity)
    {

        $total = $entity->total_male_students + $entity->total_female_students;
        return $total;
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //show staff selected in multiselected dropdown, chosenselec
        $curricularId = $_SESSION['curricularId'];
        $curricularStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionCurricularStaff');
        $getStaff = $curricularStaff->find()->select(['staff_id'])
            ->where([$curricularStaff->aliasField('institution_curricular_id') => $curricularId])->toArray();
        $staff = [];
        $count = 0;
        if (!empty($getStaff)) {
            foreach ($getStaff as $key => $value) {
                $staff[$key] = ['id' => $value->staff_id];
            }
        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($staff) {
            return $results->map(function ($row) use ($staff) {
                $row['staff_id'] = $staff;
                return $row;
            });
        });
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        //POCOR-8028 removed academic period
        $institutionId = $this->getInstitutionID();
        $query
            ->select([
                'name' => $this->aliasField('name'),
                'category' => $this->aliasField('category'),
                'CurricularType' => 'CurricularTypes.name',
                'Institution_name' => 'Institutions.name',
                'Institution_code' => 'Institutions.code',
                'female_students' => $this->aliasField('total_female_students'),
                'male_students' => $this->aliasField('total_male_students'),
            ])
            ->contain(['Institutions', 'CurricularTypes'])
            ->where([$this->aliasField('institution_id') => $institutionId]);

    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        //POCOR-8028 removed academic period
        $newArray = [];
        $newArray[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];
        $newArray[] = [
            'key' => 'category_name',
            'field' => 'category_name',
            'type' => 'string',
            'label' => __('Category')
        ];
        $newArray[] = [
            'key' => 'CurricularType',
            'field' => 'CurricularType',
            'type' => 'string',
            'label' => __('Curricular Type')
        ];
        $newArray[] = [
            'key' => 'Institution_name',
            'field' => 'Institution_name',
            'type' => 'string',
            'label' => __('Institution name')
        ];
        $newArray[] = [
            'key' => 'Institution_code',
            'field' => 'Institution_code',
            'type' => 'string',
            'label' => __('Institution code')
        ];

        $newArray[] = [
            'key' => 'total_female_students',
            'field' => 'total_female_students',
            'type' => 'string',
            'label' => __('Female Students')
        ];
        $newArray[] = [
            'key' => 'total_male_students',
            'field' => 'total_male_students',
            'type' => 'string',
            'label' => __('Male Students')
        ];
        $fields->exchangeArray($newArray);
    }

    public function onExcelGetCategoryName(Event $event, Entity $entity)
    {
        if ($entity->category == 1) {
            return 'Curricular';
        } else {
            return 'Extracurricular';
        }
    }

    //POCOR-7691 start
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionCurriculars';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
        session_start();
        $paramPass = $this->request->getParam('pass');
        if(!empty($paramPass[1])){
            $ids = isset($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : 0;
            $curricularId = $ids['id'];
            //$curricularId = $this->paramsDecode($this->request->pass[1])['id'];
            $_SESSION["curricularId"] = $curricularId;
        }
    }
    //POCOR-7691 end
}