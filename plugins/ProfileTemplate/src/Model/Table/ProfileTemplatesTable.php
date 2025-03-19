<?php
namespace ProfileTemplate\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;

class ProfileTemplatesTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST ALL_SUBJECTS = 2;
    CONST SELECT_SUBJECTS = 1;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->behaviors()->get('Download')->setConfig(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->setConfig(
            'content',
            'excel_template'
        );
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['view']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadTemplate'] = 'downloadTemplate';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ])
            // ->add('generate_start_date', 'ruleInAcademicPeriod', [
            //     'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            // ])
            // ->add('generate_end_date', [
            //     'ruleInAcademicPeriod' => [
            //         'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            //     ],
            //     'ruleCompareDateReverse' => [
            //         'rule' => ['compareDateReverse', 'generate_start_date', false]
            //     ]
            // ])
            ->allowEmpty('excel_template');
    }

    public function validationSubjects(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator->requirePresence('subjects');
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['excel_template_name']['visible'] = false;
        $this->field('generate_start_date', ['type' => 'date']);
        $this->field('generate_end_date', ['type' => 'date']);
        $this->field('excel_template');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['description']['visible'] = false;
        $this->setFieldOrder(['code', 'name', 'generate_start_date', 'generate_end_date', 'excel_template']);
		$this->setupTabElements();
	}

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $institutionPositionsTable = TableRegistry::get('Institution.InstitutionPositions');
        $StaffTable = TableRegistry::get('Institution.Staff');
        $alreadyAssignedStaffs = $StaffTable->find()->select([
            'institution_position_id' => $StaffTable->aliasField('institution_position_id'),
            'status_id' => $institutionPositionsTable->aliasField('status_id')
        ])->innerJoin([$institutionPositionsTable->getAlias() => $institutionPositionsTable->getTable()], [
            $institutionPositionsTable->aliasField('id = ') . $StaffTable->aliasField('institution_position_id'),
        ])->where([
            $StaffTable->aliasField('institution_id') => 6,
            $StaffTable->aliasField('staff_id') => 8810,
        ])
        // ->hydrate(false)->toArray();
        ->toArray();
        $expectedStaffStatuses = [];
        foreach ($alreadyAssignedStaffs AS $staff) {
            $expectedStaffStatuses[$staff['status_id']] = $staff['status_id'];
        }
        return $expectedStaffStatuses;

        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery['academic_period_id']) ? $this->request->getQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        $extra['elements']['controls'] = ['name' => 'ProfileTemplate.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    private function setupFields($entity)
    {
        $this->field('code');
        $this->field('name');
        $this->field('description');
        $this->field('academic_period_id', ['entity' => $entity]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->excel_template;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->getConfig(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'generate_start_date', 'generate_end_date', 'excel_template']);
    }

    public function onGetExcelTemplate(Event $event, Entity $entity)
    {
        if ($entity->has('excel_template_name')) {
            return $entity->excel_template_name;
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        // to set template download button
        $downloadUrl = $this->url('downloadTemplate');
        $this->controller->set('downloadOnClick', "javascript:window.location.href='". Router::url($downloadUrl) ."'");
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'generate_start_date', 'generate_end_date', 'excel_template']);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {

    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->fields['code']['type'] = 'readonly';
        $this->fields['name']['type'] = 'readonly';
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'generate_start_date', 'generate_end_date', 'excel_template']);
    }

    // public function onUpdateFieldExcelTemplate(Event $event, array $attr, $action, Request $request)
    public function onUpdateFieldExcelTemplate(Event $event, array $attr, $action)
    {
        if ($action == 'index' || $action == 'view') {
            $attr['type'] = 'string';
        } elseif($action == 'edit') { //POCOR-8903
            $requestId = $this->request->getParam('pass')[1]; 
            $paramsDecode = $this->paramsDecode($requestId);
            $recordId = $paramsDecode['id']; // Added semicolon

            $record = $this->find()
                ->where([$this->aliasField('id') => $recordId])
                ->first();
            $excelName = $record ? $record->excel_template_name : null;
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
            $attr['value'] = $excelName;
            $attr['attr']['value'] = $excelName;
        }else{
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;

        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->academic_period_id;
            $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;
        }
        return $attr;
    }

    public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        if (empty($entity->getErrors())) {
            if ($entity->teacher_comments_required == self::ALL_SUBJECTS) {
                $entity->teacher_comments_required = 1;
            }
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
       
    }

    public function checkIfHasTemplate($reportCardId=0)
    {
        $hasTemplate = false;

        if (!empty($reportCardId)) {
            $entity = $this->get($reportCardId);
            $hasTemplate = !empty($entity->excel_template) ? true : false;
        }

        return $hasTemplate;
    }

    public function downloadTemplate()
    {
        $filename = 'profile_template';
        $fileType = 'xlsx';
        $filepath = WWW_ROOT . 'export' . DS . 'customexcel'. DS . 'default_templates'. DS . $filename . '.' . $fileType;

        // header("Pragma: public", true);
        // header("Expires: 0"); // set expiration time
        // header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // header("Content-Type: application/force-download");
        // header("Content-Type: application/octet-stream");
        // header("Content-Type: application/download");
        // header("Content-Disposition: attachment; filename=".basename($filepath));
        // header("Content-Transfer-Encoding: binary");
        // header("Content-Length: ".filesize($filepath));
        // echo file_get_contents($filepath);
        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Expires: 0');

            readfile($filepath);
            exit;
        } 
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {

        if (!empty($entity->generate_start_date)) {
            $entity->generate_start_date = (new FrozenDate($entity->generate_start_date))->modify('+2 day')->format('Y-m-d H:i:s');
        }

        if (!empty($entity->generate_end_date)) {
            $entity->generate_end_date = (new FrozenDate($entity->generate_end_date))->modify('+2 day')->format('Y-m-d H:i:s');
        }        

    } 

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (!empty($data['generate_start_date'])) {
            $data['generate_start_date'] = (new FrozenDate($data['generate_start_date']))->format('Y-m-d H:i:s');
        }

        if (!empty($data['generate_end_date'])) {
            $data['generate_end_date'] = (new FrozenDate($data['generate_end_date']))->format('Y-m-d H:i:s');
        }
    }
	
	private function setupTabElements() {
		$options['type'] = 'StaffTemplates';
		$tabElements = $this->getStaffTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Templates');
	}

	public function getStaffTabElements($options = [])
    {
        $tabElements = [];
        $tabUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $templateUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $tabElements = [
            'Profiles' => ['text' => __('Profile')],
            'Templates' => ['text' => __('Templates')]
        ];
		
        $tabElements['Profiles']['url'] = array_merge($tabUrl, ['action' => 'InstitutionProfiles']);
        $tabElements['Templates']['url'] = array_merge($tabUrl, ['action' => 'Institutions']);

		return $tabElements;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'generate_start_date') {
            return __('Generate Start Date');
        } elseif ($field == 'generate_end_date') {
            return __('Generate End Date');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'excel_template') {
            return __('Excel Template');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
