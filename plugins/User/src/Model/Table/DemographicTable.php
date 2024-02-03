<?php

namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;

class DemographicTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_demographics');
        parent::initialize($config);
        $this->belongsTo('DemographicTypes', ['className' => 'FieldOption.DemographicTypes', 'foreignKey' => 'demographic_types_id']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('User.SetupTab');
        $this->excludeDefaultValidations(['security_user_id']);
        $this->toggle('remove', false); // POCOR-7934
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->getQuery();
        if (!empty($requestQuery)) {
            $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        } else {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        $query = $this
            ->find()
            ->where([$this->aliasField('security_user_id') => $userId])
            ->first();

        if (!empty($query)) {
            $this->toggle('add', false);
        }

        // Start POCOR-5188
        if ($this->request->getParam('controller') == 'Staff') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Demographic', 'Staff - General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        } elseif ($this->request->getParam('controller') == 'Students') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Demographic', 'Students - General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        } elseif ($this->request->getParam('controller') == 'Directories') {
            $is_manual_exist = $this->getManualUrl('Directory', 'Demographic', 'General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        } elseif ($this->request->getParam('controller') == 'Profiles') {
            $is_manual_exist = $this->getManualUrl('Personal', 'Demographic', 'General');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }
        // End POCOR-5188
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $demographicTypes = TableRegistry::getTableLocator()->get('FieldOption.DemographicTypes');
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

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $actions = ['view', 'edit'];
        foreach ($actions as $action) {
            if (isset($buttons[$action])) {
                $url = $buttons[$action]['url'];
                if ($url['plugin'] == 'Profile' && $url['controller'] == 'Profiles' && $url['action'] == 'Demographic') {
                    if (isset($url[2])) {
                        unset($url[2]);
                    }
                    $queryString = $this->getQueryString();
                    $queryString['id'] = $entity->id;
                    $url[1] = $this->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                }
            }
        }
//        die('<pre>' . print_r($buttons, true));
        return $buttons;
    }

    public function beforeAction($event)
    {
        $gradeOptions = $this->getIndigenousOptions();
        $this->fields['indigenous']['type'] = 'select';
        $this->fields['indigenous']['options'] = $gradeOptions;
        $this->fields['security_user_id']['visible'] = false;
    }

    public function getIndigenousOptions()
    {
        $IndigenousOptions = array();
        $IndigenousOptions[0] = 'Yes';
        $IndigenousOptions[1] = 'No';
        $IndigenousOptions[2] = 'Unknown';

        return $IndigenousOptions;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {

        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    /*POCOR-6395 starts*/
    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $requestQuery = $this->request->getQuery();
        if (!empty($requestQuery)) {
            $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        } else {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        $entity['security_user_id'] = $userId;
    }

    //POCOR-6395 ends
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $requestQuery = $this->request->getQuery();
        if (!empty($requestQuery)) {
            $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        } else {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        $query->where([$this->aliasField('security_user_id') => $userId])
            ->orderDesc($this->aliasField('id'));
    }


    public function editBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $requestQuery = $this->request->getQuery();
        if (!empty($requestQuery)) {
            $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        } else {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }

        $entity['security_user_id'] = $userId;

    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $requestQuery = $this->request->getQuery();
        if (!empty($requestQuery)) {
            $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        } else {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        $entity['security_user_id'] = $userId;
    }
}
