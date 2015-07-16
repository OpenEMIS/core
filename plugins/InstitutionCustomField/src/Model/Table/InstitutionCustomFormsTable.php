<?php
namespace InstitutionCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionCustomFormsTable extends CustomFormsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'infrastructure_site_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomFields', [
			'className' => 'InstitutionCustomField.InstitutionCustomFields',
			'joinTable' => 'institution_site_custom_forms_fields',
			'foreignKey' => 'institution_site_custom_form_id',
			'targetForeignKey' => 'institution_site_custom_field_id',
			'through' => 'InstitutionCustomField.InstitutionCustomFormsFields',
			'dependent' => true
		]);
	}
}
