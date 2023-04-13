<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\I18n\Time;


//POCOR-7271
class SurveyRecipientsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_surveys');
        parent::initialize($config);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
        $this->toggle('add', false);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $name = array('Institution > Overview','Institution > Students > Survey','Institution > Repeater > Survey');
        $CustomModules = TableRegistry::get('custom_modules');
        $moduleOptions =  $CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code']) 
           ->where(['custom_modules.name IN' => $name]);

        $surveyForm = TableRegistry::get('survey_forms');
        $surveyFormOption = $surveyForm->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        if (!empty($moduleOptions)) {
            $selectedModule = $this->queryString('module', $moduleOptions);
            $selectedModuleSecond = $this->queryString('form', $surveyFormOption);
            $extra['toolbarButtons']['add']['url']['module'] = $selectedModule;
            $extra['toolbarButtons']['add']['url']['form'] = $selectedModuleSecond;
            $extra['elements']['controls'] = [
            'name' => 'CustomField.controls',
            'data' => [
                    'module' => $selectedModule,
                    'form' => $selectedModuleSecond,
                ],
                'options' => [],
                'order' => 1
            ];
            $this->controller->set(compact('moduleOptions','surveyFormOption'));
        }
        $institutions = TableRegistry::get('institutions');
        $surveyForm = TableRegistry::get('survey_forms');
        $SurveyFormFilters = TableRegistry::get('survey_forms_filters');
        $query
            ->select(['institution_name'=> $institutions->aliasField('name'),
                        'institution_code'=> $institutions->aliasField('code')])
            ->leftJoin([$institutions->alias() => $institutions->table()],
                [$institutions->aliasField('id').'='.$this->aliasField('institution_id')])
            ->leftJoin([$surveyForm->alias() => $surveyForm->table()],
                [$surveyForm->aliasField('id').'='.$this->aliasField('survey_form_id')])
            ->leftJoin([$SurveyFormFilters->alias() => $SurveyFormFilters->table()],
                [$SurveyFormFilters->aliasField('survey_form_id').'='.$surveyForm->aliasField('id')])
            ->group([$this->aliasField('institution_id')]);

        $this->field('institution_code',['visible' => true]);
        $this->field('institution_name', ['visible' => true]);
        $this->field('status_id', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('survey_form_id', ['visible' => false]);
        $this->field('institution_id', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);
        $this->setFieldOrder([
        'institution_code', 'institution_name']);

        
    }

}
