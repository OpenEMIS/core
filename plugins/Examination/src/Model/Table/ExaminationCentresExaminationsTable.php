<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsTable extends ControllerActionTable
{
    private $queryString;
    private $examCentreId;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsToMany('ExaminationItems', [
            'className' => 'Examination.ExaminationItems',
            'joinTable' => 'examination_centres_examinations_subjects',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'examination_item_id',
            'through' => 'Examination.ExaminationCentresExaminationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('LinkedInstitutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'examination_centres_examinations_institutions',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'institution_id',
            'through' => 'Examination.ExaminationCentresExaminationsInstitutions',
            'dependent' => true
        ]);
        $this->belongsToMany('Invigilators', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centres_examinations_invigilators',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'invigilator_id',
            'through' => 'Examination.ExaminationCentresExaminationsInvigilators',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'joinTable' => 'examination_centre_examinations_students',
            'foreignKey' => ['examination_centre_id', 'examination_id'],
            'targetForeignKey' => 'student_id',
            'through' => 'Examination.ExaminationCentreStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index']
        ]);
        $this->addBehavior('CompositeKey');
        $this->setDeleteStrategy('restrict');
        $this->toggle('edit', false);
        $this->toggle('search', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('examination_centres', 'create')
            ->requirePresence('examination_id')
            ->requirePresence('academic_period_id');

        return $validator;
    }

    public function validationAllExaminationCentres(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->requirePresence('examination_centres', false)
            ->remove('examination_centres');
        return $validator;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url']['action'] = 'Exams';
            $buttons['view']['url'][1] = $this->paramsEncode(['id' => $entity->examination_id]);
        }

        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action != 'add') {
            $this->controller->getExamCentresTab();
            $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

            // Set the header of the page
            $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
            $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Examinations'));
        }

        $this->field('examination_id', ['type' => 'select', 'sort' => ['field' => 'Examinations.name']]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }

        $this->setFieldOrder(['academic_period_id', 'examination_id', 'total_registered']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // sort
        $sortList = ['Examinations.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $query->where([$this->aliasField('examination_centre_id') => $this->examCentreId]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['redirect']['action'] = 'ExamCentres';

        if (isset($extra['toolbarButtons']['back'])) {
            $extra['toolbarButtons']['back']['url']['action'] = 'ExamCentres';
        }

        $this->field('academic_period_id');
        $this->field('examination_centre_type');
        $this->field('link_all_examination_centres');
        $this->field('examination_centres');
        $this->fields['total_registered']['visible'] = false;
        $this->setFieldOrder(['academic_period_id', 'examination_id', 'examination_centre_type', 'link_all_examination_centres', 'examination_centres']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['onChangeReload'] = true;
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['type'] = 'select';
            return $attr;
        }
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['academic_period_id'])) {
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $examOptions = $this->Examinations->getExaminationOptions($academicPeriodId);
                $attr['options'] = $examOptions;
            }

            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldExaminationCentreType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $Institutions = TableRegistry::get('Institution.Institutions');
            $typeOptions = $Institutions->Types
                ->find('list')
                ->find('visible')
                ->toArray();

            $attr['options'] = $typeOptions + ['-1' => __('Non-Institution')];
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;

            return $attr;
        }
    }

    public function onUpdateFieldLinkAllExaminationCentres(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : 0;
            $type = isset($request->data[$this->alias()]['examination_centre_type']) ? $request->data[$this->alias()]['examination_centre_type'] : 0;

            $examCentreOptions = $this->ExaminationCentres
                ->find('NotLinkedExamCentres', ['examination_id' => $examinationId, 'examination_centre_type' => $type])
                ->count();

            $selectOptions = [];
            if ($examCentreOptions != 0) {
                $yesOption = __('Yes') . ' - ' . $examCentreOptions . ' ' . __('examination centres selected');
                $selectOptions = [0 => __('No'), 1 => $yesOption];
            }

            $attr['type'] = 'select';
            $attr['options'] = $selectOptions;
            $attr['select'] = false;
            $attr['default'] = 0; // default selected is no
            $attr['onChangeReload'] = 'changeLinkAllExaminationCentres';
            return $attr;
        }
    }

    public function addOnChangeLinkAllExaminationCentres(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_centres', $data[$this->alias()])) {
                $data[$this->alias()]['examination_centres'] = '';
            }
        }
    }

    public function onUpdateFieldExaminationCentres(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $linkAllExaminationCentres = isset($request->data[$this->alias()]['link_all_examination_centres']) ? $request->data[$this->alias()]['link_all_examination_centres'] : 0;

            if ($linkAllExaminationCentres == 1) {
                $attr['type'] = 'hidden';

            } else {
                $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : 0;
                $type = isset($request->data[$this->alias()]['examination_centre_type']) ? $request->data[$this->alias()]['examination_centre_type'] : 0;

                $examCentreOptions = $this->ExaminationCentres
                    ->find('NotLinkedExamCentres', ['examination_id' => $examinationId, 'examination_centre_type' => $type])
                    ->order([$this->ExaminationCentres->aliasField('code')])
                    ->toArray();

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $examCentreOptions;
                $attr['fieldName'] = $this->alias().'.examination_centres';
            }

            return $attr;
        }
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['examination_centre_id'] = 0;

        if ($requestData[$this->alias()]['link_all_examination_centres'] == 1) {
            $patchOptions['validate'] = 'allExaminationCentres';
        }

        // Subjects logic
        $examinationId = $requestData[$this->alias()]['examination_id'];
        $examinationItems = $this->ExaminationItems->getExaminationItemSubjects($examinationId);

        $examinationCentreSubjects = [];
        if (is_array($examinationItems)) {
            foreach($examinationItems as $item) {
                $examinationCentreSubjects[] = [
                    'id' => $item->item_id,
                    '_joinData' => [
                        'education_subject_id' => $item->education_subject_id
                    ]
                ];
            }
        }

        $requestData[$this->alias()]['examination_items'] = $examinationCentreSubjects;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (isset($requestData[$model->alias()]['examination_centres']) && !empty($requestData[$model->alias()]['examination_centres'])) {
                $examCentreIds = $requestData[$model->alias()]['examination_centres'];
                $newEntities = [];

                if (is_array($examCentreIds)) {
                    $patchOptions['associated'] = ['ExaminationItems._joinData' => ['validate' => false]];

                    foreach ($examCentreIds as $centreId) {
                        $requestData[$model->alias()]['examination_centre_id'] = $centreId;
                        $newEntities[] = $model->newEntity($requestData->getArrayCopy(), $patchOptions);

                    }
                }

                return $model->saveMany($newEntities);

            } else if (isset($requestData[$model->alias()]['link_all_examination_centres']) && $requestData[$model->alias()]['link_all_examination_centres'] == 1) {
                if (!empty($requestData[$this->alias()]['examination_id'])) {
                    $examinationId = $requestData[$model->alias()]['examination_id'];
                    $academicPeriodId = $requestData[$model->alias()]['academic_period_id'];
                    $examCentreTypeId = !empty($requestData[$model->alias()]['examination_centre_type']) ? $requestData[$model->alias()]['examination_centre_type'] : '';

                    $examItems = [];
                    if (isset($requestData[$model->alias()]['examination_items'])) {
                        foreach($requestData[$model->alias()]['examination_items'] as $obj) {
                            $examItems[] = [
                                'examination_item_id' => $obj['id'],
                                'education_subject_id' => $obj['_joinData']['education_subject_id']
                            ];
                        }
                    }

                    // put subjects into System Processes params
                    $SystemProcesses = TableRegistry::get('SystemProcesses');
                    $name = 'LinkAllExamCentres';
                    $pid = '';
                    $processModel = $model->registryAlias();
                    $eventName = '';
                    $passArray = ['examination_items' => $examItems];
                    $params = json_encode($passArray);
                    $systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $params);

                    $this->triggerLinkAllExamCentresShell($systemProcessId, $examinationId, $academicPeriodId, $examCentreTypeId);
                    $this->Alert->warning($this->aliasField('savingProcessStarted'), ['reset' => true]);
                    return true;
                }
            }
        };

        return $process;
    }

    private function triggerLinkAllExamCentresShell($systemProcessId, $examinationId, $academicPeriodId, $examCentreTypeId = null)
    {
        $args = '';
        $args .= !is_null($systemProcessId) ? ' '.$systemProcessId : '';
        $args .= !is_null($examinationId) ? ' '.$examinationId : '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';
        $args .= !is_null($examCentreTypeId) ? ' '.$examCentreTypeId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake LinkAllExamCentres '.$args;
        $logs = ROOT . DS . 'logs' . DS . 'LinkAllExamCentres.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch(\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when link all exam centres : '. $ex);
        }
    }
}
