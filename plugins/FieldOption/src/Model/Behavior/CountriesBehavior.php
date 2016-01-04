<?php
namespace FieldOption\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\ORM\Query;

class CountriesBehavior extends DisplayBehavior {
	use UtilityTrait;

	private $fieldOptionName;
	private $associations = ['identity_type_id' => ['plugin' => 'FieldOption', 'code' => 'IdentityTypes', 'contain' => 'IdentityTypes']];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
		$events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
		return $events;
	}

	public function initialize(array $config) {
		parent::initialize($config);	
		$this->fieldOptionName = $config['fieldOptionName'];
	}	

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {		
		parent::indexBeforeAction($event, $query, $settings);
		
		$table = TableRegistry::get($this->fieldOptionName);
		$query = $table->find();
		$this->displayAssociatedFields($table);
		return $query;
	}	

	public function viewBeforeAction(Event $event) {
		parent::viewBeforeAction($event);

		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayAssociatedFields($table);		
		return $table;
	}

	public function addEditBeforeAction(Event $event) {
		parent::indexBeforeAction($event);

		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayAssociatedFields($table);
		return $table;
	}

	public function displayAssociatedFields($table) {
		/**
		 * assign $table's associations to $this->_table, which is the FieldOptionValues
		 */
		$table->ControllerAction = $this->_table->ControllerAction;
		// $associations = ['security_users'];
		$associations = [];
		foreach ($table->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') {
				if (!in_array($assoc->table(), $associations)) {
					$this->_table->belongsTo($assoc->target()->alias(), ['className' => $assoc->target()->registryAlias(), 'foreignKey' => $assoc->foreignKey()]);
				}
			}
		}
		/**
		 * end assignment
		 */
		
		$schema = $table->schema();
		$columns = $schema->columns();
		foreach ($columns as $key => $attr) {
			//check whether column has another set of values to be retrieved
			if(array_key_exists($attr, $this->associations)) {
				
				$attrFieldOptionPluginName = $this->associations[$attr]['plugin']; 
				$attrFieldOptionCodeName = $this->associations[$attr]['code']; 
				$attrFieldOptionId = TableRegistry::get('FieldOption.FieldOptions')->find()
										->where(['plugin' => $attrFieldOptionPluginName])
										->andWhere(['code' => $attrFieldOptionCodeName])
										->first();
				$options = TableRegistry::get('FieldOption.FieldOptionValues')->find('list')->where(['field_option_id' => $attrFieldOptionId->id])->toArray();
				
				switch ($this->_table->action) {
				 	case 'index':case 'view':
				 		$options = [0 => '']+$options;
				 		break;
				 	
				 	case 'edit':case 'add':
				 		$options = [0 => __('-- Select Option --')]+$options;
				 		break;
				 	
				 	default:
				 		$options = [0 => __('-- Select Option --')]+$options;
				 		break;
				}
				$this->_table->ControllerAction->field($attr, ['type' => 'select', 
															   'options' => $options, 
															   'visible' => true, 
															   'model' => $table->alias(),
															   'className' => $this->fieldOptionName
															   ]);
			}
			
		}	
	}

	public function onGetIdentityTypeId(Event $event, Entity $entity) {
		return $entity->identity_type->name;
	}

}
