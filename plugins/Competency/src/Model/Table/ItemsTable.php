<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;

use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class ItemsTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('competency_items');

        parent::initialize($config);

        $this->belongsTo('Templates',       ['className' => 'Competency.Templates', 'foreignKey' => ['competency_template_id', 'academic_period_id']]);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->hasMany('Criterias', ['className' => 'Competency.Criterias', 'foreignKey' => ['competency_item_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Periods', ['className' => 'Competency.Periods', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentCompetencyResults', ['className' => 'Institution.StudentCompetencyResults', 'foreignKey' => ['competency_criteria_id', 'academic_period_id']]);
        
        
        $this->setDeleteStrategy('restrict');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

        //academic period filter
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));
        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;

        //template filter
        $templateOptions = $this->Templates->getTemplateByAcademicPeriod($selectedPeriod);

        if ($templateOptions) {
            $templateOptions = array(-1 => __('-- Select Template --')) + $templateOptions;
        }

        if ($request->query('template')) {
            $selectedTemplate = $request->query('template');
        } else {
            $selectedTemplate = -1;
        }

        $extra['selectedTemplate'] = $selectedTemplate;
        $data['templateOptions'] = $templateOptions;
        $data['selectedTemplate'] = $selectedTemplate;
        
        $extra['elements']['control'] = [
            'name' => 'Competency.items_controls',
            'data' => $data,
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedPeriod']) {
                $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
            }
        }

        if (array_key_exists('selectedTemplate', $extra)) {
            if ($extra['selectedTemplate']) {
                $conditions[] = $this->aliasField('competency_template_id = ') . $extra['selectedTemplate'];
            }
        }

        $query->where([$conditions]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            if ($action == 'add') {
                $attr['default'] = $selectedPeriod;
                $attr['options'] = $periodOptions;
                $attr['onChangeReload'] = 'changeAcademicPeriod';
            } else if ($action == 'edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $periodOptions[$attr['entity']->academic_period_id];
                $attr['value'] = $attr['entity']->academic_period_id;
            }
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['template'] = '-1';
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $templateOptions = $this->Templates->getTemplateByAcademicPeriod($selectedPeriod);
                
                $attr['options'] = $templateOptions;
                
            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->competency_template_id;
                $attr['attr']['value'] = $this->Templates->get([$attr['entity']->competency_template_id, $attr['entity']->academic_period_id])->code_name;

            }
        }
        return $attr;
    }

    public function onUpdateFieldMandatory(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        
        return $attr;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('competency_template_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('mandatory', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'name', 'mandatory'
        ]);
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function getItemByTemplateAcademicPeriod($selectedTemplate, $selectedPeriod)
    {
        return $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('competency_template_id') => $selectedTemplate
                ])
                ->toArray();
    }
}
