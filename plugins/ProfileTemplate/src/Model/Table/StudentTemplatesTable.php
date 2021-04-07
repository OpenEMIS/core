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
use Cake\I18n\Date;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;

class StudentTemplatesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
		$this->table('student_profile_templates');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

		$this->addBehavior('User.AdvancedNameSearch');
		
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->behaviors()->get('Download')->config(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->config(
            'content',
            'excel_template'
        );
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['view']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadTemplate'] = 'downloadTemplate';
        return $events;
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ])
            ->add('generate_start_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('generate_end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'generate_start_date', false]
                ]
            ])
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
        // Academic Period filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
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
        $this->behaviors()->get('ControllerAction')->config(
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

    public function onUpdateFieldExcelTemplate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'index' || $action == 'view') {
            $attr['type'] = 'string';
        } else {
            // attr for template download button
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
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
        $filename = 'student_profile_template';
        $fileType = 'xlsx';
        $filepath = WWW_ROOT . 'export' . DS . 'customexcel'. DS . 'default_templates'. DS . $filename . '.' . $fileType;

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".basename($filepath));
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filepath));
        echo file_get_contents($filepath);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {

        if (!empty($entity->generate_start_date)) {
            $entity->generate_start_date = (new Date($entity->generate_start_date))->format('Y-m-d H:i:s');
        }

        if (!empty($entity->generate_end_date)) {
            $entity->generate_end_date = (new Date($entity->generate_end_date))->format('Y-m-d H:i:s');
        }        

    } 
	
	private function setupTabElements() {
		$options['type'] = 'StaffTemplates';
		$tabElements = $this->getStudentTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Templates');
	}

	public function getStudentTabElements($options = [])
    {
        $tabElements = [];
        $tabUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $templateUrl = ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates'];
        $tabElements = [
            'Profiles' => ['text' => __('Profiles')],
            'Templates' => ['text' => __('Templates')]
        ];
		
        $tabElements['Profiles']['url'] = array_merge($tabUrl, ['action' => 'StudentProfiles']);
        $tabElements['Templates']['url'] = array_merge($tabUrl, ['action' => 'Students']);

		return $tabElements;
    }
	
}
