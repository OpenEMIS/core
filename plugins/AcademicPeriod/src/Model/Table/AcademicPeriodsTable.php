<?php
namespace AcademicPeriod\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Exception\NotFoundException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;
use Cake\I18n\Date;

class AcademicPeriodsTable extends AppTable
{
    private $_fieldOrder = ['visible', 'current', 'editable', 'code', 'name', 'start_date', 'end_date', 'academic_period_level_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Parents', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Levels', ['className' => 'AcademicPeriod.AcademicPeriodLevels', 'foreignKey' => 'academic_period_level_id']);

        // reference to itself
        $this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'parent_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        // other associated modules
        $this->hasMany('AssessmentAssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAssessmentItemResults', ['className' => 'Institution.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Assessments', ['className' => 'Assessment.Assessments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAttendances', ['className' => 'Institution.StudentAttendances', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentClasses', ['className' => 'Student.StudentClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionFees', ['className' => 'Institution.InstitutionFees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRubrics', ['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionQualityVisits', ['className' => 'Quality.InstitutionQualityVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RepeaterSurveys', ['className' => 'InstitutionRepeater.RepeaterSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionInstitutionSubjects', ['className' => 'Institution.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
        // not required. left here for reference
        // $this->hasMany('ReportInstitutionSubjects', ['className' => 'Report.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferOut', ['className' => 'Institution.StudentTransferOut', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferIn', ['className' => 'Institution.StudentTransferIn', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('WithdrawRequests', ['className' => 'Institution.WithdrawRequests', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentSurveys', ['className' => 'Student.StudentSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Students', ['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentFees', ['className' => 'Institution.StudentFees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentPromotion', ['className' => 'Institution.StudentPromotion', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransfer', ['className' => 'Institution.StudentTransfer', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UndoStudentStatus', ['className' => 'Institution.UndoStudentStatus', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Programmes', ['className' => 'Student.Programmes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RubricStatusPeriods', ['className' => 'Rubric.RubricStatusPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffExtracurriculars', ['className' => 'Staff.Extracurriculars', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentExtracurriculars', ['className' => 'Student.Extracurriculars', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SurveyStatusPeriods', ['className' => 'Survey.SurveyStatusPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'dependent' => true]);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true]);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true]);
        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true]);

        $this->hasMany('Examinations', ['className' => 'Examination.Examinations', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentres', ['className' => 'Examination.ExaminationCentres', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentresExaminations', ['className' => 'Examination.ExaminationCentresExaminations', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentresExaminationsStudents', ['className' => 'Examination.ExaminationCentresExaminationsStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationItemResults', ['className' => 'Examination.ExaminationItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Scholarships', ['className' => 'Scholarship.Scholarships', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Tree');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index'],
            'Results' => ['index'],
            'StudentExaminationResults' => ['index'],
            'OpenEMIS_Classroom' => ['index', 'view'],
            'InstitutionStaffAttendances' => ['index', 'view'],
            'StudentAttendances' => ['index', 'view'],
            'ScheduleTimetable' => ['index']
        ]);
        
        $this->addBehavior('Institution.Calendar');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $additionalParameters = ['editable = 1 AND visible > 0'];
        //POCOR-5917 starts
        return $validator
            ->add('end_date', [
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', false]
                ],
                'ruleCompareEndDate' => [
                    'rule' => ['compareEndDate', 'start_date', false],
                    'message' => __('End date should not be less than current date')
                ]
            ])//POCOR-5917 ends
            ->add('current', 'ruleValidateNeeded', [
                'rule' => ['validateNeeded', 'current', $additionalParameters],
            ])
            ;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity->start_year = date("Y", strtotime($entity->start_date));
        $entity->end_year = date("Y", strtotime($entity->end_date));
        //POCOR-5917 starts
        if (!$entity->isNew()) { //when edit academic period
            $acedmicPeriodData = $this->find()->where([$this->aliasField('id') => $entity->id])->first();
            $entity->old_end_date = (new Date($acedmicPeriodData->end_date))->format('Y-m-d');
            $entity->old_end_year = $acedmicPeriodData->end_year;
        }
        //POCOR-5917 ends
        if ($entity->current == 1) {
            $entity->editable = 1;
            $entity->visible = 1;

            // Adding condition on updateAll(), only change the one which is not the current academic period.
            $where = [];
            if (!$entity->isNew()) {
                $where['id <> '] = $entity->id; // same with $where = [0 => 'id <> ' . $entity->id];
            }
            $this->updateAll(['current' => 0], $where);
        }
    }

    public function onBeforeDelete(Event $event, ArrayObject $options, $ids)
    {
        $entity = $this->find()->select(['current'])->where($ids)->first();

        // die silently when a non super_admin wants to delete
        if (!$this->AccessControl->isAdmin()) {
            $event->stopPropagation();
            $this->controller->redirect($this->ControllerAction->url('index'));
        }

        // do not allow for deleting of current
        if (!empty($entity) && $entity->current == 1) {
            $event->stopPropagation();
            $this->Alert->warning('general.currentNotDeletable');
            $this->controller->redirect($this->ControllerAction->url('index'));
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (!$this->AccessControl->isAdmin()) {
            if (array_key_exists('remove', $buttons)) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        //POCOR-5917 starts
        if(isset($entity->old_end_date) && !empty($entity->old_end_date) && isset($entity->old_end_year) && !empty($entity->old_end_year)){ //when edit academic period
            $academic_end_date = (new Date($entity->old_end_date))->format('Y-m-d'); 
            $academic_end_year = $entity->old_end_year; 
            $institutionStudents = TableRegistry::get('institution_students');
            $institutionStudentsData = $institutionStudents
                                            ->find()
                                            ->where([
                                                $institutionStudents->aliasField('end_date') => $academic_end_date,
                                                $institutionStudents->aliasField('end_year') => $academic_end_year,
                                                $institutionStudents->aliasField('student_status_id') => 1
                                            ])->toArray();
            if(!empty($institutionStudentsData)){
                foreach ($institutionStudentsData as $key => $val) {
                    $institution_students_end_date = (new Date($entity->end_date))->format('Y-m-d');
                    $institution_students_end_year = $entity->end_year;
                    $institutionStudentsEntity = $this->patchEntity($val, ['end_date' => $institution_students_end_date, 'end_year' =>$institution_students_end_year], ['validate' =>false]);

                    $institutionStudents->save($institutionStudentsEntity);  
                }
            }                                
        }
        //POCOR-5917 ends
        $canCopy = $this->checkIfCanCopy($entity);

        $shells = ['Infrastructure', 'Shift'];
        if ($canCopy) {
            // only trigger shell to copy data if is not empty
            if ($entity->has('copy_data_from') && !empty($entity->copy_data_from)) {
                $copyFrom = $entity->copy_data_from;
                $copyTo = $entity->id;
                foreach ($shells as $shell) {
                    $this->triggerCopyShell($shell, $copyFrom, $copyTo);
                }
            }
        }

        if ($entity->dirty('current')) { //check whether default value has been changed
            if ($entity->current) {
                $this->triggerUpdateInstitutionShiftTypeShell($entity->id);
            }
        }

        $broadcaster = $this;
        $listeners = [];
        $listeners[] = TableRegistry::get('Institution.InstitutionLands');
        $listeners[] = TableRegistry::get('Institution.InstitutionBuildings');
        $listeners[] = TableRegistry::get('Institution.InstitutionFloors');
        $listeners[] = TableRegistry::get('Institution.InstitutionRooms');

        if (!empty($listeners)) {
            $this->dispatchEventToModels('Model.AcademicPeriods.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $this->addAfterSave($event, $entity, $requestData);
    }

    public function beforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_level_id');
        $this->fields['start_year']['visible'] = false;
        $this->fields['end_year']['visible'] = false;
        $this->fields['school_days']['visible'] = false;
        $this->fields['lft']['visible'] = false;
        $this->fields['rght']['visible'] = false;
    }

    public function afterAction(Event $event)
    {
        $this->ControllerAction->field('current');
        $this->ControllerAction->field('copy_data_from', [
            'type' => 'hidden',
            'value' => 0,
            'after' => 'current'
        ]);
        $this->ControllerAction->field('editable');
        foreach ($this->_fieldOrder as $key => $value) {
            if (!in_array($value, array_keys($this->fields))) {
                unset($this->_fieldOrder[$key]);
            }
        }
        $this->ControllerAction->setFieldOrder($this->_fieldOrder);
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain('Levels');
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['current'] = $entity->current;
        $this->ControllerAction->field('visible');

        // set academic_period_level_id to not editable to prevent any classes/subjects to not in Year level
        $this->fields['academic_period_level_id']['type'] = 'readonly';
        $this->fields['academic_period_level_id']['value'] = $entity->academic_period_level_id;
        $this->fields['academic_period_level_id']['attr']['value'] = $entity->level->name;
    }

    public function indexBeforeAction(Event $event)
    {
        // Add breadcrumb
        $toolbarElements = [
            ['name' => 'AcademicPeriod.breadcrumb', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $this->fields['parent_id']['visible'] = false;

        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
        if ($parentId != 0) {
            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();
            $this->controller->set('crumbs', $crumbs);
        } else {
            $results = $this
                ->find('all')
                ->select([$this->aliasField('id')])
                ->where([$this->aliasField('parent_id') => 0])
                ->all();

            if ($results->count() == 1) {
                $parentId = $results
                    ->first()
                    ->id;

                $action = $this->ControllerAction->url('index');
                $action['parent'] = $parentId;
                return $this->controller->redirect($action);
            }
        }
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
        $query->where([$this->aliasField('parent_id') => $parentId]);
    }

    public function addEditBeforeAction(Event $event)
    {
        //Setup fields
        $this->_fieldOrder = ['academic_period_level_id', 'code', 'name'];

        $this->fields['parent_id']['type'] = 'hidden';
        $parentId = $this->request->query('parent');

        if (is_null($parentId)) {
            $this->fields['parent_id']['attr']['value'] = -1;
        } else {
            $this->fields['parent_id']['attr']['value'] = $parentId;

            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();

            $parentPath = '';
            foreach ($crumbs as $crumb) {
                $parentPath .= $crumb->name;
                $parentPath .= $crumb === end($crumbs) ? '' : ' > ';
            }

            $this->ControllerAction->field('parent', [
                'type' => 'readonly',
                'attr' => ['value' => $parentPath]
            ]);

            array_unshift($this->_fieldOrder, 'parent');
        }
    }

    public function triggerUpdateInstitutionShiftTypeShell($params)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateInstitutionShiftType ' . $params;
        $logs = ROOT . DS . 'logs' . DS . 'UpdateInstitutionShiftType.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function onGetCurrent(Event $event, Entity $entity)
    {
        return $entity->current == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    // For PHPOE-1916
    public function onGetEditable(Event $event, Entity $entity)
    {
        return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }
    // End PHPOE-1916

    public function onGetName(Event $event, Entity $entity)
    {
        return $event->subject()->HtmlField->link($entity->name, [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => $this->alias,
            'index',
            'parent' => $entity->id
        ]);
    }

    public function onUpdateFieldAcademicPeriodLevelId(Event $event, array $attr, $action, Request $request)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : 0;
        $results = $this
            ->find()
            ->select([$this->aliasField('academic_period_level_id')])
            ->where([$this->aliasField('id') => $parentId])
            ->all();

        $attr['type'] = 'select';
        if (!$results->isEmpty()) {
            $data = $results->first();
            $levelId = $data->academic_period_level_id;

            $levelResults = $this->Levels
                        ->find()
                        ->select([$this->Levels->aliasField('level')])
                        ->where([$this->Levels->aliasField('id') => $levelId])
                        ->all();

            if (!$levelResults->isEmpty()) {
                $levelData = $levelResults->first();
                $level = $levelData->level;

                $levelOptions = $this->Levels
                            ->find('list')
                            ->where([$this->Levels->aliasField('level >') => $level])
                            ->toArray();
                $attr['options'] = $levelOptions;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCurrent(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        $attr['onChangeReload'] = 'changeCurrent';

        return $attr;
    }

    public function onUpdateFieldCopyDataFrom(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_level_id', $request->data[$this->alias()])) {
                    $academicPeriodLevelId = $request->data[$this->alias()]['academic_period_level_id'];
                    $level = $this->Levels
                        ->find()
                        ->order([$this->Levels->aliasField('level ASC')])
                        ->first();
                    $current = $request->query('current');

                    if (!is_null($current) && $current == 1) {
                        $where = [$this->aliasField('academic_period_level_id') => $level->id];
                        if (array_key_exists('id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['id'])) {
                            $currentAcademicPeriodId = $request->data[$this->alias()]['id'];
                            $currentAcademicPeriodOrder = $this->get($currentAcademicPeriodId)->order;
                            $where[$this->aliasField('id <>')] = $currentAcademicPeriodId;
                            $where[$this->aliasField('order >')] = $currentAcademicPeriodOrder;
                        }

                        $copyDataFromOptions = $this
                            ->find('list')
                            ->find('order')
                            ->where($where)
                            ->toArray();

                        $attr['type'] = 'select';
                        $attr['options'] = $copyDataFromOptions;
                        $attr['select'] = false;
                    }
                }
            }
        }

        return $attr;
    }

    public function onUpdateFieldEditable(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['current'])) {
            if ($request->data[$this->alias()]['current'] == 1) {
                $attr['type'] = 'hidden';
            }
        }
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    public function onUpdateFieldVisible(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['current'])) {
            if ($request->data[$this->alias()]['current'] == 1) {
                $attr['type'] = 'hidden';
            }
        }
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    public function addEditOnChangeCurrent(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['current']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('current', $request->data[$this->alias()])) {
                    $request->query['current'] = $request->data[$this->alias()]['current'];
                }
            }
        }
    }

    public function getYearList($params = [])
    {
        $conditions = array_key_exists('conditions', $params) ? $params['conditions'] : [];
        $withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : false;
        $isEditable = array_key_exists('isEditable', $params) ? $params['isEditable'] : null;

        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();

        $data = $this
            ->find('list')
            ->find('years')
            ->find('editable', ['isEditable' => $isEditable])
            ->where($conditions)
            ->toArray();

        if (!$withLevels) {
            $list = $data;
        } else {
            $list[$level->name] = $data;
        }

        return $list;
    }

    public function findSchoolAcademicPeriod(Query $query, array $options)
    {
        $query
            ->find('visible')
            ->find('years')
            ->find('editable', ['isEditable' => true])
            ->find('order')
            ->where([
                $this->aliasField('parent_id') . ' <> ' => 0
            ]);

        return $query;
    }

    public function getList($params=[])
    {
        $withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : true;
        $withSelect = array_key_exists('withSelect', $params) ? $params['withSelect'] : false;
        $isEditable = array_key_exists('isEditable', $params) ? $params['isEditable'] : null;
        $restrictLevel = array_key_exists('restrictLevel', $params) ? $params['restrictLevel'] : null;

        if (!$withLevels) {
            $where = [
                $this->aliasField('current') => 1,
                $this->aliasField('parent_id') . ' <> ' => 0
            ];

            if (!empty($restrictLevel)) {
                $where['academic_period_level_id IN '] = $restrictLevel;
            }

            // get the current period
            $data = $this->find('list')
                ->find('visible')
                ->find('order')
                ->where($where)
                ->toArray();

            // get all other periods
            $where[$this->aliasField('current')] = 0;
            $data += $this->find('list')
                ->find('visible')
                ->find('editable', ['isEditable' => $isEditable])
                ->find('order')
                ->where($where)
                ->toArray();
        } else {
            $where = [
                $this->aliasField('parent_id') . ' <> ' => 0,
            ];

            if (!empty($restrictLevel)) {
                $where['academic_period_level_id IN '] = $restrictLevel;
            }

            // get the current period
            $data = $this->find()
                ->find('visible')
                ->find('editable', ['isEditable' => $isEditable])
                ->contain(['Levels'])
                ->where($where)
                ->order([$this->aliasField('academic_period_level_id'), $this->aliasField('order')])
                ->toArray();

            $levelName = "";
            $list = [];

            foreach ($data as $key => $obj) {
                if ($levelName != $obj->level->name) {
                    $levelName = __($obj->level->name);
                }

                $list[$levelName][$obj->id] = __($obj->name);
            }

            $data = $list;
        }

        if ($withSelect) {
            $data = ['' => '-- ' . __('Select Period') .' --'] + $data;
        }

        return $data;
    }

    public function findEditable(Query $query, array $options)
    {
        $isEditable = array_key_exists('isEditable', $options) ? $options['isEditable'] : null;
        if (is_null($isEditable)) {
            return $query;
        } else {
            return $query->where([$this->aliasField('editable') => (bool)$isEditable]);
        }
    }

    public function getDate($dateObject)
    {
        if (is_object($dateObject)) {
            return $dateObject->toDateString();
        }
        return false;
    }

    public function getWorkingDaysOfWeek()
    {
        // $weekdays = [
        //  0 => __('Sunday'),
        //  1 => __('Monday'),
        //  2 => __('Tuesday'),
        //  3 => __('Wednesday'),
        //  4 => __('Thursday'),
        //  5 => __('Friday'),
        //  6 => __('Saturday'),
        // ];

        $weekdays = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
        $week = [];
        for ($i=0; $i<$daysPerWeek; $i++) {
            $week[] = $weekdays[$firstDayOfWeek++];
            $firstDayOfWeek = $firstDayOfWeek % 7;
        }
        return $week;
    }

    public function getAttendanceWeeks($id)
    {
        // $weekdays = array(
        //  0 => 'sunday',
        //  1 => 'monday',
        //  2 => 'tuesday',
        //  3 => 'wednesday',
        //  4 => 'thursday',
        //  5 => 'friday',
        //  6 => 'saturday',
        //  //7 => 'sunday'
        // );

        $period = $this->findById($id)->first();
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');

        // If First of week is sunday changed the value to 7, because sunday with the '0' value unable to be displayed
        if ($firstDayOfWeek == 0) {
            $firstDayOfWeek = 7;
        }

        $daysPerWeek = $ConfigItems->value('days_per_week');

        // If last day index is '0'-valued-sunday it will change the value to '7' so it will be displayed.
        $lastDayIndex = ($firstDayOfWeek - 1);// last day index always 1 day before the starting date.
        if ($lastDayIndex == 0) {
            $lastDayIndex = 7;
        }

        $startDate = $period->start_date;

        $weekIndex = 1;
        $weeks = [];

        do {
            $endDate = $startDate->copy()->next($lastDayIndex);
            if ($endDate->gt($period->end_date)) {
                $endDate = $period->end_date;
            }
            $weeks[$weekIndex++] = [$startDate, $endDate];
            $startDate = $endDate->copy();
            $startDate->addDay();
        } while ($endDate->lt($period->end_date));

        return $weeks;
    }

    public function getEditable($academicPeriodId)
    {
        try {
            return $this->get($academicPeriodId)->editable;
        } catch (RecordNotFoundException $e) {
            return false;
        }
    }

    public function getAvailableAcademicPeriods($list = true, $order='DESC')
    {
        if ($list) {
            $query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        } else {
            $query = $this->find();
        }
        $result = $query->where([
                        $this->aliasField('editable') => 1,
                        $this->aliasField('visible') . ' >' => 0,
                        $this->aliasField('parent_id') . ' >' => 0
                    ])
                    ->order($this->aliasField('name') . ' ' . $order);
        if ($result) {
            return $result->toArray();
        } else {
            return false;
        }
    }

    public function getCurrent()
    {
        $query = $this->find()
                    ->select([$this->aliasField('id')])
                    ->where([
                        $this->aliasField('editable') => 1,
                        $this->aliasField('visible').' > 0',
                        $this->aliasField('current') => 1,
                        $this->aliasField('parent_id').' > 0',
                    ])
                    ->order(['start_date DESC']);
        $countQuery = $query->count();
        if ($countQuery > 0) {
            $result = $query->first();
            return $result->id;
        } else {
            $query = $this->find()
                    ->select([$this->aliasField('id')])
                    ->where([
                        $this->aliasField('editable') => 1,
                        $this->aliasField('visible').' > 0',
                        $this->aliasField('parent_id').' > 0',
                    ])
                    ->order(['start_date DESC']);
            $countQuery = $query->count();
            if ($countQuery > 0) {
                $result = $query->first();
                return $result->id;
            } else {
                return 0;
            }
        }
    }

    public function generateMonthsByDates($startDate, $endDate)
    {
        $result = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        // $stampToday = strtotime(date('Y-m-d'));

        $stampFirstDayOfMonth = strtotime('01-' . date('m', $stampStartDay) . '-' . date('Y', $stampStartDay));
        // while($stampFirstDayOfMonth <= $stampEndDay && $stampFirstDayOfMonth <= $stampToday){
        while ($stampFirstDayOfMonth <= $stampEndDay) {
            $monthString = date('F', $stampFirstDayOfMonth);
            $monthNumber = date('m', $stampFirstDayOfMonth);
            $year = date('Y', $stampFirstDayOfMonth);

            $result[] = [
                'month' => ['inNumber' => $monthNumber, 'inString' => $monthString],
                'year' => $year
            ];

            $stampFirstDayOfMonth = strtotime('+1 month', $stampFirstDayOfMonth);
        }

        return $result;
    }

    public function generateDaysOfMonth($year, $month, $startDate, $endDate)
    {
        $days = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        // $stampToday = strtotime(date('Y-m-d'));

        $stampFirstDayOfMonth = strtotime($year . '-' . $month . '-01');
        $stampFirstDayNextMonth = strtotime('+1 month', $stampFirstDayOfMonth);

        if ($stampFirstDayOfMonth <= $stampStartDay) {
            $tempStamp = $stampStartDay;
        } else {
            $tempStamp = $stampFirstDayOfMonth;
        }
        // while($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth && $tempStamp < $stampToday){
        while ($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth) {

            $weekDay = date('l', $tempStamp);
            $date = date('Y-m-d', $tempStamp);
            $day = date('d', $tempStamp);

            $dateObj = new Date($tempStamp);
            $dayFormat = __($dateObj->format('l')) . ' (' . $this->formatDate($dateObj) . ') ';

            $days[] = [
                'weekDay' => $weekDay,
                'date' => $date,
                'day' => $day,
                'dayFormat' => $dayFormat
            ];

            $tempStamp = strtotime('+1 day', $tempStamp);
        }

        return $days;
    }

    public function findYears(Query $query, array $options)
    {
        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();

        return $query
            ->find('visible')
            ->find('order')
            ->where([$this->aliasField('academic_period_level_id') => $level->id]);
    }

    public function findWeeklist(Query $query, array $options)
    {
        $model = $this;

        $query->formatResults(function (ResultSetInterface $results) use ($model) {
            return $results->map(function ($row) use ($model) {
                $academicPeriodId = $row->id;

                $todayDate = date("Y-m-d");
                $weekOptions = [];

                $weeks = $model->getAttendanceWeeks($academicPeriodId);
                $weekStr = __('Week') . ' %d (%s - %s)';
                $currentWeek = null;

                foreach ($weeks as $index => $dates) {
                    $startDay = $dates[0]->format('Y-m-d');
                    $endDay = $dates[1]->format('Y-m-d');
                    $weekAttr = [];
                    if ($todayDate >= $startDay && $todayDate <= $endDay) {
                        $weekStr = __('Current Week') . ' %d (%s - %s)';
                        $weekAttr['current'] = true;
                        $currentWeek = $index;
                    } else {
                        $weekStr = __('Week') . ' %d (%s - %s)';
                    }

                    $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                    $weekAttr['start_day'] = $startDay;
                    $weekAttr['end_day'] = $endDay;
                    $weekOptions[$index] = $weekAttr;
                }

                $row->weeks = $weekOptions;

                return $row;
            });
        });
    }

    private function checkIfCanCopy(Entity $entity)
    {
        $canCopy = false;

        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();

        // if is year level and set to current
        if ($entity->academic_period_level_id == $level->id && $entity->current == 1) {
            $canCopy = true;
        }

        return $canCopy;
    }

    public function triggerCopyShell($shellName, $copyFrom, $copyTo)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$copyFrom.' '.$copyTo;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'_copy.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function getLatest()
    {
        $query = $this->find()
                ->select([$this->aliasField('id')])
                ->where([
                    $this->aliasField('editable') => 1,
                    $this->aliasField('visible').' > 0',
                    $this->aliasField('parent_id').' > 0',
                    $this->aliasField('academic_period_level_id') => 1
                ])
                ->order(['start_date DESC']);
        $countQuery = $query->count();
        if ($countQuery > 0) {
            $result = $query->first();
            return $result->id;
        } else {
            return 0;
        }
    }

    public function getAcademicPeriodId($startDate, $endDate)
    {
        // get the academic period id from startDate and endDate (e.g. delete the absence records not showing the academic period id)
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');

        $academicPeriod = $this->find()
            ->where([
                $this->aliasField('start_date') . ' <= ' => $startDate,
                $this->aliasField('end_date') . ' >= ' => $endDate,
                $this->aliasField('code') . ' <> ' => 'all'
            ])
            ->first();

        $academicPeriodId = $academicPeriod->id;

        return $academicPeriodId;
    }

    public function getAcademicPeriodIdByDate($date)
    {
        // get the academic period id from date
        $date = $date->format('Y-m-d');

        $academicPeriod = $this->find()
            ->where([
                $this->aliasField('start_date') . ' <= ' => $date,
                $this->aliasField('end_date') . ' >= ' => $date,
                $this->aliasField('code') . ' <> ' => 'all'
            ])
            ->first();

        $academicPeriodId = $academicPeriod->id;

        return $academicPeriodId;
    }

    public function getMealWeeksForPeriod($academicPeriodId){
        $model = $this;
        $query = $this->AcademicPeriods->find()
                ->where([$this->aliasField('id') => $academicPeriodId])
                 ->all();
     


         $todayDate = date("Y-m-d");
                    $weekOptions = [];
                    $selectedIndex = 0;

                    $weeks = $model->getAttendanceWeeks($academicPeriodId);

                    $weekStr = __('Week') . ' %d (%s - %s)';
                    $currentWeek = null;

                    foreach ($weeks as $index => $dates) {
                        $startDay = $dates[0]->format('Y-m-d');
                        $endDay = $dates[1]->format('Y-m-d');
                        $weekAttr = [];
                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            $weekStr = __('Current Week') . ' %d (%s - %s)';
                            // $weekAttr['selected'] = true;
                            $currentWeek = $index;
                        } else {
                            $weekStr = __('Week') . ' %d (%s - %s)';
                        }

                        $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                        $weekAttr['start_day'] = $startDay;
                        $weekAttr['end_day'] = $endDay;
                        $weekAttr['id'] = $index;
                        $weekOptions[] = $weekAttr;

                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            end($weekOptions);
                            $selectedIndex = key($weekOptions);
                        }
                    }

                    $weekOptions[$selectedIndex]['selected'] = true;
                   
                    
            return $weekOptions;
       
    }

    
    public function findWeeksForPeriod(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $model = $this;
        
        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($model) {
                return $results->map(function ($row) use ($model) {
                    $academicPeriodId = $row->id;

                    $todayDate = date("Y-m-d");
                    $weekOptions = [];
                    $selectedIndex = 0;

                    $weeks = $model->getAttendanceWeeks($academicPeriodId);
                    $weekStr = __('Week') . ' %d (%s - %s)';
                    $currentWeek = null;

                    foreach ($weeks as $index => $dates) {
                        $startDay = $dates[0]->format('Y-m-d');
                        $endDay = $dates[1]->format('Y-m-d');
                        $weekAttr = [];
                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            $weekStr = __('Current Week') . ' %d (%s - %s)';
                            // $weekAttr['selected'] = true;
                            $currentWeek = $index;
                        } else {
                            $weekStr = __('Week') . ' %d (%s - %s)';
                        }

                        $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                        $weekAttr['start_day'] = $startDay;
                        $weekAttr['end_day'] = $endDay;
                        $weekAttr['id'] = $index;
                        $weekOptions[] = $weekAttr;

                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            end($weekOptions);
                            $selectedIndex = key($weekOptions);
                        }
                    }

                    $weekOptions[$selectedIndex]['selected'] = true;
                    $row->weeks = $weekOptions;

                    return $row;
                });
            });
    }

    public function findPeriodHasClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $currentYearId = $this->getCurrent();

        return $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name')
            ])
            ->find('years')
            ->matching('InstitutionClasses', function ($q) use ($institutionId) {
                return $q->where(['InstitutionClasses.institution_id' => $institutionId]);
            })
            ->group([$this->aliasField('id')])
            ->formatResults(function (ResultSetInterface $results) use ($currentYearId) {
                return $results->map(function ($row) use ($currentYearId) {
                    if ($row->id == $currentYearId) {
                        $row->selected = true;
                    }
                    return $row;
                });
            });
    }

    public function findWorkingDayOfWeek(Query $query, array $options)
    {
        $workingDayOfWeek = $this->getWorkingDaysOfWeek();

        $dayOfWeek = [];
        foreach ($workingDayOfWeek as $index => $day) {
            $dayOfWeek[] = [
                'day_of_week' => $index + 1,
                'day' => $day
            ];
        }
        
        return $query->formatResults(function (ResultSetInterface $results) use ($dayOfWeek) {
            return $dayOfWeek;
        });
    }

    public function findDaysForPeriodWeek(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $weekId = $options['week_id'];
        $institutionId = $options['institution_id'];

        // pass true if you need school closed data
        if (array_key_exists('school_closed_required', $options)) {
            $schoolClosedRequired = $options['school_closed_required'];
        } else {
            $schoolClosedRequired = false;
        }

        $model = $this;

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $weeks = $model->getAttendanceWeeks($academicPeriodId);
        $week = $weeks[$weekId];

        if (isset($options['exclude_all']) && $options['exclude_all']) {
            $dayOptions = [];
        } else {
            $dayOptions[] = [
                'id' => -1,
                'name' => __('All Days'),
                'date' => -1
            ];
        }

        $schooldays = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            // sunday should be '7' in order to be displayed
            $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
        }

        $firstDayOfWeek = $week[0]->copy();
        $today = null;

        do {
            if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
                if ($schoolClosedRequired == false) {
                    $schoolClosed = false;
                } else {
                    $schoolClosed = $this->isSchoolClosed($firstDayOfWeek, $institutionId);
                }
                $suffix = $schoolClosed ? __('School Closed') : '';

                $data = [
                    'id' => $firstDayOfWeek->dayOfWeek,
                    'day' => __($firstDayOfWeek->format('l')),
                    'name' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ') ' . $suffix,
                    'date' => $firstDayOfWeek->format('Y-m-d'),
                ];

                if ($schoolClosed) {
                    $data['closed'] = true;
                }

                $dayOptions[] = $data;

                if (is_null($today) || $firstDayOfWeek->isToday()) {
                    end($dayOptions);
                    $today = key($dayOptions);
                }
            }
            $firstDayOfWeek->addDay();
        } while ($firstDayOfWeek->lte($week[1]));

        if (!is_null($today)) {
            $dayOptions[$today]['selected'] = true;
        }

        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($dayOptions) {
                return $dayOptions;
            });
    }

    public function getNextAcademicPeriodId($id)
    {
        $selectedPeriod = $id;
        $periodLevelId= $this->get($selectedPeriod)->academic_period_level_id;
        $startDate= $this->get($selectedPeriod)->start_date->format('Y-m-d');

        $where = [
            $this->aliasField('id <>') => $selectedPeriod,
            $this->aliasField('academic_period_level_id') => $periodLevelId,
            $this->aliasField('start_date >=') => $startDate
        ];

        $nextAcademicPeriodId = $this->AcademicPeriods
            ->find('visible')
            ->find('editable', ['isEditable' => true])
            ->where($where)
            ->order([$this->aliasField('order') => 'DESC'])
            ->extract('id')
            ->first();

        return $nextAcademicPeriodId;
    }
}
