<?php

namespace User\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\Validation\Validator;

class AwardsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_awards');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('Staff.StaffTab');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Awards' =>['id']
            ]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('award', true)
            ->notEmptyString('award', __('This field cannot be left empty'))
            ->requirePresence('issuer', true)
            ->notEmptyString('issuer', __('This field cannot be left empty'));
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $controller = $this->request->getParam('controller');

        if (($controller === 'Students' || $controller === 'Directories') && !empty($queryString['student_id'])) {
            $securityUserId = $queryString['student_id'];
        } elseif (!empty($queryString['staff_id'])) {
            $securityUserId = $queryString['staff_id'];
        } elseif (!empty($queryString['user_id'])) {
            $securityUserId = $queryString['user_id'];
        } elseif (!empty($queryString['security_user_id'])) {
            $securityUserId = $queryString['security_user_id'];
        } elseif (!empty($queryString['id'])) {
            $securityUserId = $queryString['id'];
        } else {
            $securityUserId = null;
        }

        $this->field('security_user_id', ['type' => 'hidden', 'value' => $securityUserId]);
    }
    
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserID();
        if(empty($userId)){
            $queryString = $this->getQueryString();
            $userId = isset($queryString['staff_id']) ? $queryString['staff_id'] : $queryString['student_id'] ;
        }
        if(empty($userId)){ //POCOR-8316
            $userId = $this->Auth->user('id');
        }

        $query->where([$this->aliasField('security_user_id') => $userId]);

        // Start POCOR-5188
        if ($this->request->getParam('controller') == 'Staff') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Awards', 'Staff - Professional');
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
            $is_manual_exist = $this->getManualUrl('Institutions', 'Awards', 'Students - Academic');
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
            $is_manual_exist = $this->getManualUrl('Directory', 'Awards', 'Staff - Professional');
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

    private function setupTabElements()
    {
        switch ($this->controller->getName()) {
            case 'Students':
                //$tabElements = $this->controller->getAcademicTabElements();
                $tabElements = $this->getAcademicTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
            /*POCOR-6267 starts*/
            case 'GuardianNavs':
                $tabElements = $this->controller->getAcademicTabElements();
                //$tabElements = $this->getAcademicTabElements();
                if($this->controller->getName() == 'GuardianNavs') {
                    $tabElements = $this->controller->getAcademicTabElements($options);
                }
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
            /*POCOR-6267 ends*/
            case 'Staff':
                //$tabElements = $this->controller->getProfessionalTabElements();
                $tabElements = $this->getProfessionalTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
            case 'Directories':
                //Shikha's Code[START]
                $tabElements = $this->getProfessionalTabElements();
                $type = $this->request->getQuery('type');
                $options['type'] = $type;
                if($type == 'student') {
                    $tabElements = $this->controller->getAcademicTabElements($options);
                }
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction',$this->getAlias());
                break;
                //Shikha's Code[END]
            case 'Profiles':
                $type = $this->request->getQuery('type');
                $options['type'] = $type;
                $session = $this->request->getSession();
                $isStaff = $session->read('Auth.User.is_staff');
                if ($isStaff) {
                    //$tabElements = $this->controller->getProfessionalTabElements($options);
                    $tabElements = $this->getProfessionalTabElements($options);
                } else if ($this->action == 'index') {
                    //$tabElements = $this->controller->getAcademicTabElements($options);
                    $tabElements = $this->getAcademicTabElements($options);
                } elseif ($type == 'student') {
                    //$tabElements = $this->controller->getAcademicTabElements($options);
                    $tabElements = $this->getAcademicTabElements($options);
                } else {
                    //$tabElements = $this->controller->getProfessionalTabElements($options);
                    $tabElements = $this->getProfessionalTabElements($options);
                }

                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->getAlias());
                break;
        }
    }

    //Function Uncommented for ask POCOR-6267
    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'modified_user_id') {
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
