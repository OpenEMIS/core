<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Traits\OptionsTrait;

class ConfigAuthenticationTable extends ControllerActionTable
{
    public $id;
    public $authenticationType;
    private $options = [];

    public function initialize(array $config)
    {
        //print_r('hi'); die;
        $this->table('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.Authentication');
        $this->toggle('remove', false);
        $this->toggle('add', false);
        $this->toggle('search', false);

        $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');

        $this->options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
            ->where([
                'ConfigItemOptions.option_type' => 'yes_no',
                'ConfigItemOptions.visible' => 1
            ])
            ->toArray();

    }

    public function validationDefault(Validator $validator)
    {
        return $validator->add('value', 'ruleLocalLogin', [
                    'rule' => 'checkLocalLogin'
                ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = $this->buildSystemConfigFilters($this->action);
        $extra['config']['selectedLink'] = ['controller' => 'Configurations', 'action' => 'index'];
        $this->field('visible', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('name', ['visible' => ['index'=>true]]);
        $this->field('type', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('value', ['visible' => true]);
        $this->field('value_selection', ['visible' => ['index'=>false, 'view'=>true, 'edit'=>true]]);
        $this->field('default_value', ['visible' => ['view'=>true]]);

        if($this->action == 'view') {
            if (isset($extra['toolbarButtons']['back'])) {
                unset($extra['toolbarButtons']['back']);
            }
            $extra['elements']['controls'] = $this->buildSystemConfigFilters($this->action);
            $this->checkController();
        }
        $this->checkController();

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Authentication','System Configurations');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {   //POCOR-7156 starts
        if (in_array($action, ['edit', 'add'])) {
            $id= $this->paramsDecode($request->params['pass'][1]);
            if (!empty($id)) {
                $entity = $this->get($id);
                $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
                if ($entity->field_type == 'Dropdown' && $entity->option_type == 'yes_no') {
                    $this->options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                        ->where([
                            'ConfigItemOptions.option_type' => 'yes_no',
                            'ConfigItemOptions.visible' => 1
                        ])
                        ->toArray();
                    $attr['options'] = $this->options;
                    $attr['onChangeReload'] = true;
                }elseif($entity->field_type == 'Dropdown' && $entity->option_type == 'completeness'){
                    $this->options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                        ->where([
                            'ConfigItemOptions.option_type' => 'completeness',
                            'ConfigItemOptions.visible' => 1
                        ])
                        ->toArray();
                    $attr['options'] = $this->options;
                    $attr['onChangeReload'] = true;
                }
            }
        }//POCOR-7156 ends
        return $attr;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['type']['attr']['value'] = __($entity->type);
        $this->fields['label']['attr']['value'] = __($entity->label);
    }

    public function onGetType(Event $event, Entity $entity)
    {
        return __($entity->type);
    }

    public function onGetLabel(Event $event, Entity $entity)
    {
        return __($entity->label);
    }
    //POCOR-7156 starts
    public function onGetName(Event $event, Entity $entity)
    {
        if($entity->code == 'enable_local_login'){
            return __('Authentication Provider');
        }
    }//POCOR-7156 ends

    public function onGetValue(Event $event, Entity $entity)
    {   //POCOR-7156 starts
        if($entity->code == 'enable_local_login'){
            return __('Local');
        }elseif($entity->code == 'two_factor_authentication'){
            if($entity->value == 1){
                return __('Enable');
            }else{
                return __('Disable');
            }
        }else{//POCOR-7156 ends
            return __($this->options[$entity->value]);
        }
    }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        if($entity->code == 'enable_local_login'){
            return __($this->options[$entity->default_value]);
        }elseif($entity->code == 'two_factor_authentication'){
            if($entity->default_value == 1){
                return __('Enable');
            }else{
                return __('Disable');
            }
        }
    }
    //POCOR-7156 starts
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->find('visible')
            ->where([$this->aliasField('type') => 'Authentication', $this->aliasField('visible') => 1]);
    }//POCOR-7156 ends
}
