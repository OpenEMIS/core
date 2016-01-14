<?php
namespace CustomField\Model\Behavior;

use Cake\Utility\Inflector;
use Cake\ORM\Behavior;

class SetupBehavior extends Behavior {
	protected $fieldTypeCode;
	protected $fieldType;

	public function initialize(array $config) {
        parent::initialize($config);

        $class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('Setup', '', $class);
		$class = str_replace('Behavior', '', $class);

		$code = strtoupper(Inflector::underscore($class));
		$this->_table->setFieldTypes($code);
		$this->fieldTypeCode = $code;
		$this->fieldType = $class;
    }

    protected function sortFieldOrder($field=null) {
    	$fields = $this->_table->fields;

    	$order = 0;
		$fieldOrder = [];
		$ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
		if (!is_null($field)) {
			array_unshift($ignoreFields, $field);
		}

		foreach ($fields as $fieldName => $fieldAttr) {
			if (!in_array($fieldName, $ignoreFields)) {
				$order = $fieldAttr['order'] > $order ? $fieldAttr['order'] : $order;
				$fieldOrder[$fieldAttr['order']] = $fieldName;
			}
		}

		foreach ($ignoreFields as $key => $field) {
			$fieldOrder[++$order] = $field;
		}

		ksort($fieldOrder);
		$this->_table->ControllerAction->setFieldOrder($fieldOrder);
    }
}
