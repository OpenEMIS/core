<?php
namespace Rubric\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\Event\Event;

class RubricStatusesTable extends AppTable
{
    private $_contain = ['RubricTemplates', 'AcademicPeriods', 'SecurityRoles', 'Programmes'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
        $this->belongsToMany('AcademicPeriods', [
            'className' => 'AcademicPeriod.AcademicPeriods',
            'joinTable' => 'rubric_status_periods',
            'foreignKey' => 'rubric_status_id',
            'targetForeignKey' => 'academic_period_id'
        ]);
        $this->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'rubric_status_roles',
            'foreignKey' => 'rubric_status_id',
            'targetForeignKey' => 'security_role_id'
        ]);
        $this->belongsToMany('Programmes', [
            'className' => 'Education.EducationProgrammes',
            'joinTable' => 'rubric_status_programmes',
            'foreignKey' => 'rubric_status_id',
            'targetForeignKey' => 'education_programme_id'
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'rubric_template_id';
    }

    public function beforeAction(Event $event)
    {
        $this->ControllerAction->field('rubric_template_id', ['type' => 'select']);
        $this->ControllerAction->field('academic_period_level');
        $this->ControllerAction->field('academic_periods');
        $this->fields['status']['visible'] = false;

        $this->ControllerAction->addField('security_roles', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Security Roles'),
            'visible' => true
        ]);
        $this->ControllerAction->addField('programmes', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Programmes'),
            'visible' => true
        ]);

        $this->ControllerAction->setFieldOrder([
            'rubric_template_id', 'date_enabled', 'date_disabled', 'academic_period_level', 'academic_periods', 'security_roles', 'programmes'
        ]);
    }

    public function indexBeforePaginate(Event $event, $request, Query $query, ArrayObject $options)
    {
        $query->contain($this->_contain);

        $requestData = $request->getData();
        if (!empty($requestData['Search']['searchField'])) {
            $search = trim($requestData['Search']['searchField']);

            $query->where([$this->RubricTemplates->aliasField('name').' LIKE' => '%' . $search . '%']);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain($this->_contain);
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
		$academicPeriodIds = [];
		if(!empty($entity->academic_periods)) {
			foreach($entity->academic_periods as $academic_period) {
				$academicPeriodIds[] = $academic_period->id;
			}
		}
        //Setup fields
        list($securityRoleOptions, , $programmeOptions) = array_values($this->getSelectOptions($academicPeriodIds));

        $this->fields['security_roles']['options'] = $securityRoleOptions;
        $this->fields['programmes']['options'] = $programmeOptions;
    }

    public function onUpdateFieldAcademicPeriodLevel(Event $event, array $attr, $action, ServerRequest $request)
    {
        $AcademicPeriodLevels = TableRegistry::get('AcademicPeriod.AcademicPeriodLevels');
        $levelOptions = $AcademicPeriodLevels->getList()->toArray();

        $attr['options'] = $levelOptions;
        $attr['onChangeReload'] = 'changePeriod';
        if ($action != 'add') {
            $attr['visible'] = false;
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriods(Event $event, array $attr, $action, ServerRequest $request)
    {
        $selectedLevel = key($this->fields['academic_period_level']['options']);
        if ($this->request->is('post')) {
            $selectedLevel = $this->request->getData($this->aliasField('academic_period_level'));
        }

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodOptions = $AcademicPeriods
            ->find('list')
            ->find('visible')
            ->find('order')
            ->where([$AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])
            ->toArray();

        $attr['type'] = 'chosenSelect';
        $attr['placeholder'] = __('Select Academic Periods');
        $attr['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
        return $attr;
    }

    public function getSelectOptions($academicPeriodIds)
    {
        //Return all required options and their key
        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $securityRoleOptions = $SecurityRoles
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();
        $selectedSecurityRole = key($securityRoleOptions);

        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $programmeOptions = [];
		if(!empty($academicPeriodIds)) {
			$programmeOptions = $EducationProgrammes
				->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
				->find('visible')
				->find('order')
				->contain(['EducationCycles.EducationLevels.EducationSystems'])
				->where(['EducationSystems.academic_period_id IN' => $academicPeriodIds])
				->toArray();
		}
		$selectedProgramme = [];	
		if(!empty($programmeOptions)) {
			$selectedProgramme = key($programmeOptions);
		}
        return compact('securityRoleOptions', 'selectedSecurityRole', 'programmeOptions', 'selectedProgramme');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'rubric_template_id') {
            return __('Rubric Template');
        }elseif ($field == 'date_enabled') {
            return __('Date Enable');
        }elseif ($field == 'date_disabled') {
            return __('Date Disable');
        }elseif ($field == 'security_roles') {
            return __('Security Role');
        }elseif ($field == 'academic_periods') {
            return __('Academic Period');
        }elseif ($field == 'programmes') {
            return __('Programmes');
        }elseif ($field == 'academic_period_level') {
            return __('Academic Period Level'); 
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

     public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}
