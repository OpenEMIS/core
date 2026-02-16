<?php

namespace User\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\ControllerActionTable;

use Cake\Datasource\ConnectionManager;

class UserLanguagesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->behaviors()->get('ControllerAction')->setConfig('actions.search', false);
        $this->addBehavior('Institution.InstitutionTab',
            ['implementedMethods' =>
                [
                    'setUserTabElements' => 'setUserTabElements',
                ],
            ]);
        $this->addBehavior('User.SetupTab');
        $this->addBehavior('User.UserTab');
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('Languages', ['className' => 'Languages']);
    }

    public function beforeAction($event)
    {
        $this->fields['language_id']['type'] = 'select';
        $gradeOptions = $this->getGradeOptions();
        $this->fields['listening']['type'] = 'select';
        $this->fields['listening']['options'] = $gradeOptions;
        $this->fields['listening']['translate'] = false;
        $this->fields['speaking']['type'] = 'select';
        $this->fields['speaking']['options'] = $gradeOptions;
        $this->fields['speaking']['translate'] = false;
        $this->fields['reading']['type'] = 'select';
        $this->fields['reading']['options'] = $gradeOptions;
        $this->fields['reading']['translate'] = false;
        $this->fields['writing']['type'] = 'select';
        $this->fields['writing']['options'] = $gradeOptions;
        $this->fields['writing']['translate'] = false;
        if($this->request->getParam('controller') == 'Staff') {
            $userId = $this->getUserID();
            $this->field('security_user_id', ['attr' => ['value' => $userId], 'type' => 'hidden']);
        }
    }

    /**
     * @param EventInterface $event
     * @param ArrayObject $extra
     *
     */
    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('evaluation_date');
        $this->field('language_id');
        $this->field('listening', ['visible' => true,
            'attr' => ['required' => true]]);
        $this->field('speaking', ['visible' => true,
            'attr' => [ 'required' => true]]);
        $this->field('reading', ['visible' => true,
            'attr' => ['required' => true]]);
        $this->field('writing', ['visible' => true,
            'attr' => ['required' => true]]);
    }

    public function getGradeOptions()
    {
        // Start POCOR-4824

        // $gradeOptions = array();
        // for ($i = 0; $i < 8; $i++) {
        // 	$gradeOptions[$i] = $i;
        // }
        // return $gradeOptions;

        $connection = ConnectionManager::get('default');
        $res = $connection->execute('Select * from language_proficiencies order by `order` ASC');
        $rows = $res->fetchAll('assoc');
        $lp = [];
        if (!empty($rows)) {
            foreach ($rows as $key => $value) {
                $lp[$value['id']] = $value['name'];
            }
        }
        return $lp;
        // END POCOR-4824
    }

    /**
     * @param Validator $validator
     * @return Validator
     *
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator->setProvider('custom', $this)
			->add('listening', 'notBlank', ['rule' => 'notBlank'])
			->add('speaking', 'notBlank', ['rule' => 'notBlank'])
			->add('reading', 'notBlank', ['rule' => 'notBlank'])
			->add('writing', 'notBlank', ['rule' => 'notBlank'])
           ;
    }

    /*POCOR-6267 Starts*/
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserID();

        $query->where([$this->aliasField('security_user_id') => $userId]);

        // Start POCOR-5188
        if ($this->request->getParam('controller') == 'Staff') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Languages', 'Staff - General');
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
            $is_manual_exist = $this->getManualUrl('Institutions', 'Languages', 'Students - General');
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
            $is_manual_exist = $this->getManualUrl('Directory', 'Languages', 'General');
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
            $is_manual_exist = $this->getManualUrl('Personal', 'Languages', 'General');
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
    /*POCOR-6267 Ends*/

    public
    function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }


}
