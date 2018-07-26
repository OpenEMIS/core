<?php
namespace ReportCard\Model\Behavior;

use ArrayObject;

// use Alert\Model\Behavior\AlertRuleBehavior;
use Cake\ORM\Behavior;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Log\Log;

// class EmailReportCardStatusesBehavior extends AlertRuleBehavior
class EmailTemplateBehavior extends Behavior
{
    protected $_defaultConfig = [
        'feature' => 'ReportCardEmail', 
        'name' => 'Report Card Email',
        'method' => 'Email',
        'threshold' => [],
        'placeholder' => [
            // '${total_days}' => 'Total number of unexcused absence.',
            // '${threshold}' => 'Threshold value.',
            '${user.openemis_no}' => 'Student OpenEMIS ID.',
            '${user.first_name}' => 'Student first name.',
            '${user.middle_name}' => 'Student middle name.',
            '${user.third_name}' => 'Student third name.',
            '${user.last_name}' => 'Student last name.',
            '${user.preferred_name}' => 'Student preferred name.',
            // '${user.email}' => 'Student email.',
            '${user.address}' => 'Student address.',
            '${user.postal_code}' => 'Student postal code.',
            '${user.date_of_birth}' => 'Student date of birth.',
            '${user.identity_number}' => 'Student identity number.',
            // '${user.photo_name}' => 'Student photo name.',
            // '${user.photo_content}' => 'Student photo content.',
            '${user.main_identity_type.name}' => 'Student identity type.',
            '${user.main_nationality.name}' => 'Student nationality.',
            // '${user.gender.name}' => 'Student gender.',
            '${institution.name}' => 'Institution name.',
            '${institution.code}' => 'Institution code.',
            // '${institution.address}' => 'Institution address.',
            // '${institution.postal_code}' => 'Institution postal code.',
            '${institution.contact_person}' => 'Institution contact person.',
            '${institution.telephone}' => 'Institution telephone number.',
            // '${institution.fax}' => 'Institution fax number.',
            '${institution.email}' => 'Institution email.',
            // '${institution.website}' => 'Institution website.',
        ]
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $class = basename(str_replace('\\', '/', get_class($this)));
        $class = str_replace('AlertRule', '', $class);
        $class = str_replace('Behavior', '', $class);

        switch ($this->_table->Alias()) {
            case "ReportCardEmail":
                $this->_table->addAlertRuleType($class, $this->config());
                break;
            default:
                // Do nothing
        }


        
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'ControllerAction.Model.edit.afterSave' => 'editAfterSave'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if($data['submit'] == 'save') {
            $email_templates = TableRegistry::get('email_templates');
            $updateRecord = $email_templates->get(['model_alias' => $this->_table->registryAlias(),'model_reference' => $entity->id]);

            $updateRecord->subject = $data['ReportCardEmail']['subject'];
            $updateRecord->message = $data['ReportCardEmail']['message'];
            $updateRecord->modified = Time::now();
            $updateRecord->modified_user_id = $this->_table->Auth->user('id');

            $email_templates->save($updateRecord);    
        }
    }

    public function onGetSubject(Event $event, Entity $entity)
    {
        $model = $this->_table->paramsDecode($this->_table->request->params['pass'][1]);

        $email_templates = TableRegistry::get('email_templates')
                ->find()
                ->where([
                    'model_alias' => $this->_table->registryAlias(),
                    'model_reference' => $model['id']
                ])
                ->select(['subject'])
                ->first();

        if(!is_null($email_templates) && !is_null($email_templates->subject)) {
            return $email_templates->subject;
        }
        return '';
    }

    public function onGetMessage(Event $event, Entity $entity)
    {
        $model = $this->_table->paramsDecode($this->_table->request->params['pass'][1]);

        $email_templates = TableRegistry::get('email_templates')
                            ->find()
                            ->where([
                                'model_alias' => $this->_table->registryAlias(),
                                'model_reference' => $model['id']
                            ])
                            ->select(['message'])
                            ->first();


        if(!is_null($email_templates) && !is_null($email_templates->message)) {
            return $email_templates->message;
        }
        return '';
    }

    public function onUpdateFieldSubject(Event $event, array $attr, $action, Request $request)
    {
        if($action == 'edit') {
            $model = $this->_table->paramsDecode($request->params['pass'][1]);
            $email_templates = TableRegistry::get('email_templates')
                                ->find()
                                ->where([
                                    'model_alias' => $this->_table->registryAlias(),
                                    'model_reference' => $model['id']
                                ])
                                ->select(['subject'])
                                ->first();

            if(!is_null($email_templates) && !is_null($email_templates->subject)) {
                $attr['attr']['value'] = $email_templates->subject;
            }
            return $attr;
        }
    }

    public function onUpdateFieldMessage(Event $event, array $attr, $action, Request $request)
    {
        if($action == 'edit') {
            $model = $this->_table->paramsDecode($request->params['pass'][1]);
            $email_templates = TableRegistry::get('email_templates')
                                ->find()
                                ->where([
                                    'model_alias' => $this->_table->registryAlias(),
                                    'model_reference' => $model['id']
                                ])
                                ->select(['message'])
                                ->first();


            if(!is_null($email_templates) && !is_null($email_templates->message)) {
                $attr['attr']['value'] = $email_templates->message;
            }
            return $attr;
        }
    }
}
