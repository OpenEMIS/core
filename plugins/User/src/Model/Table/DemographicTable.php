<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;
use Cake\ORM\Query;

class DemographicTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_demographics');
        parent::initialize($config);

        $this->belongsTo('DemographicTypes', ['className' => 'Student.DemographicTypes', 'foreignKey' => 'demographic_types_id']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('User.SetupTab');
        
        $this->toggle('remove', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $query = $this
            ->find()
            ->where([$this->aliasField('security_user_id') => $userId])
            ->first();

        if (!empty($query)) {
            $this->toggle('add', false);
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $demographicTypes = TableRegistry::get('FieldOption.DemographicTypes');
        $demographicTypesArray = $demographicTypes
            ->find()
            ->toArray();

        $demographicTypes->fields['demographicsTypes'] = $demographicTypesArray;
        $demographicTypes->fields['entity'] = $entity;
        $this->field('demographic_types_id', [
            'type' => 'element',
            'element' => 'User.Demographics/Demographic_description',
            'fields' => $demographicTypes->fields,
            'formFields' => [],
            'model' => 'Demographics',
            'className' => 'User.Demographics'
        ]);
    }

    public function beforeAction($event) {
        $gradeOptions = $this->getIndigenousOptions();
        $this->fields['indigenous']['type'] = 'select';
        $this->fields['indigenous']['options'] = $gradeOptions;
        $this->fields['security_user_id']['visible'] = false;
    }

    public function getIndigenousOptions() {
        $IndigenousOptions = array();
        $IndigenousOptions[0] = 'Yes';
        $IndigenousOptions[1] = 'No';
        $IndigenousOptions[2] = 'Unknown';

        return $IndigenousOptions;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'demographic_types_id') {
            return __('Wealth Quintile');
        } elseif ($field == 'indigenous') {
            return __('Indigenous People');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    /*POCOR-6395 starts*/
    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) 
    {   
        $session = $this->request->session();
        if ($this->request->params['plugin'] == 'Staff') {
            $userId = $session->read('Institution.StaffUser.primaryKey.id');
        }
        if ($this->request->params['plugin'] == 'Student') {
            $userId = $session->read('Student.Students.id');
        }
        $entity['security_user_id'] = $userId;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        if ($this->request->params['plugin'] == 'Staff') {
            $userId = $session->read('Institution.StaffUser.primaryKey.id');
        }
        if ($this->request->params['plugin'] == 'Student') {
            $userId = $session->read('Student.Students.id');
        }
        $query->where([$this->aliasField('security_user_id') => $userId])
        ->orderDesc($this->aliasField('id'));
    }
    /*POCOR-6395 ends*/
}
