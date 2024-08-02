<?php

namespace User\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class DemographicTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_demographics');
        parent::initialize($config);
        $this->belongsTo('DemographicTypes', ['className' => 'FieldOption.DemographicTypes', 'foreignKey' => 'demographic_types_id']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('Institution.InstitutionTab',
            ['implementedMethods' =>
                [
                    'setUserTabElements' => 'setUserTabElements',
                ],
            ]
        );
        $this->addBehavior('User.SetupTab');
        $this->addBehavior('User.UserTab');
        $this->excludeDefaultValidations(['security_user_id']);
        //$this->toggle('remove', false); // POCOR-7934
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {

        $userId = $this->getUserID();
        if (empty($userId)) {
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
        $userId = $this->getUserID();
        $entity['security_user_id'] = $userId;
    }

    //POCOR-6395 ends
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserID();
        $query->where([$this->aliasField('security_user_id') => $userId])
            ->orderDesc($this->aliasField('id'));
    }


    public function editBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $userId = $this->getUserID();
        $entity['security_user_id'] = $userId;

    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $userId = $this->getUserID();
        $entity['security_user_id'] = $userId;
    }

}
