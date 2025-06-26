<?php
namespace Competency\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;

class CompetencyItemsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('competency_items');

        parent::initialize($config);

        $this->belongsTo('Templates',       ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->hasMany('Criterias', ['className' => 'Competency.CompetencyCriterias', 'foreignKey' => ['competency_item_id', 'competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'competency_template_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('Periods', [
            'className' => 'Competency.CompetencyPeriods',
            'joinTable' => 'competency_items_periods',
            'foreignKey' => ['competency_item_id', 'academic_period_id', 'competency_template_id'],
            'bindingKey' => ['id', 'academic_period_id', 'competency_template_id'],
            'targetForeignKey' => ['competency_period_id', 'academic_period_id'],
            'through' => 'Competency.CompetencyItemsPeriods',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('InstitutionCompetencyResults', ['className' => 'Institution.InstitutionCompetencyResults', 'foreignKey' => ['competency_item_id', 'competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'competency_template_id', 'academic_period_id']]);
        $this->hasMany('CompetencyItemComments', ['className' => 'Institution.InstitutionCompetencyItemComments', 'foreignKey' => ['competency_item_id', 'competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'competency_template_id', 'academic_period_id']]);

        $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //POCOR-8074-5
        $queryStringArr = $this->getQueryString();
        $queryString = $this->paramsEncode($queryStringArr); //POCOR-8074-5
        if ($queryStringArr) {
            $this->controller->getCompetencyTemplateTabs(); //POCOR-8074-5
            $academicPeriodId = $queryStringArr['academic_period_id'];
            $competencyTemplateId = $queryStringArr['competency_template_id'];
            $extra['selectedPeriod'] = $academicPeriodId;
            $extra['selectedTemplate'] = $competencyTemplateId;
            $extra['queryString'] = $queryString;

            $name = $this->Templates->get(['id' => $competencyTemplateId, 'academic_period_id' => $academicPeriodId])->name;
            $header = $name . ' - ' . __(Inflector::humanize(Inflector::underscore($this->getAlias())));
            $this->controller->set('contentHeader', $header);
            $this->controller->Navigation->substituteCrumb($this->getAlias(), $header);

        } else {
            $this->log('$queryString is not set properly', 'error'); //POCOR-8074-5
            $event->stopPropagation();
            return $this->controller->redirect(['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'Templates']);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $this->fields['competency_template_id']['type'] = 'integer';
        if (isset($extra['selectedPeriod'])) {
            if ($extra['selectedPeriod']) {
                $conditions[$this->aliasField('academic_period_id')] = $extra['selectedPeriod'];
            }
        }

        if (isset($extra['selectedTemplate'])) {
            if ($extra['selectedTemplate']) {
                $conditions[$this->aliasField('competency_template_id')] = $extra['selectedTemplate'];
            }
        }

        $query->where([$conditions]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('competency_template_id', [
            'type' => 'hidden',
            'entity' => $entity,
            'extra' => $extra
        ]);
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity,
            'extra' => $extra
        ]);
        $this->field('name', [
            'type' => 'text',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'name'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->AcademicPeriods->get($attr['extra']['selectedPeriod'])->name;
            $attr['value'] = $attr['extra']['selectedPeriod'];
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->AcademicPeriods->get([$attr['entity']->academic_period_id])->name;
            $attr['value'] = $attr['entity']->academic_period_id;
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->getQuery['template'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('academic_period_id', $request->getData($this->getAlias()))) {
                    $request->getQuery['period'] = $request->getData($this->getAlias())['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->Templates->get(['id' => $attr['extra']['selectedTemplate'], 'academic_period_id' => $attr['extra']['selectedPeriod']])->code_name;
            $attr['value'] = $attr['extra']['selectedTemplate'];

        } else if ($action == 'edit') {

            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->competency_template_id;
            $attr['attr']['value'] = $this->Templates->get([$attr['entity']->competency_template_id, $attr['entity']->academic_period_id])->code_name;

        }
        return $attr;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (empty($entity->getErrors())) {
            $extra['redirect'] = [
                'plugin' => 'Competency',
                'controller' => 'Competencies',
                'action' => 'Criterias',
                '0' => 'index',
                'item' => $entity->id,
                'queryString' => $extra['queryString']
            ];
            $this->Alert->success('Items.addSuccess', ['reset' => true]);
        }
    }

    public function findItemList(Query $query, array $options)
    {
        $templateId = $options['templateId'];
        $academicPeriodId = $options['academicPeriodId'];

        $query->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('competency_template_id') => $templateId
            ]);
        return $query;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'competency_template_id') {
            return __('Competency Template');
        } elseif ($field == 'competency_items') {
            return __('Competency Items');
        }elseif ($field == 'name') {
            return __('Name');
        }elseif ($field == 'modified_user_id') {
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
