<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class CustomFormsTable extends AppTable {
	private $filterClass = [
		'className' => 'FieldOption.FieldOptionValues',
		'joinTable' => 'custom_forms_filters',
		'foreignKey' => 'custom_form_id',
		'targetForeignKey' => 'custom_filter_id',
		'through' => 'CustomField.CustomFormsFilters',
		'dependent' => true
	];

	public function initialize(array $config) {
		if (array_key_exists('custom_filter', $config)) {
			$this->filterClass = array_merge($this->filterClass, $config['custom_filter']);
		}
		
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFilters', $this->filterClass);
		$this->belongsToMany('CustomFields', [
			'className' => 'CustomField.CustomFields',
			'joinTable' => 'custom_forms_fields',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_field_id',
			'through' => 'CustomField.CustomFormsFields',
			'dependent' => true
		]);
	}
}
