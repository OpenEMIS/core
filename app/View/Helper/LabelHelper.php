<?php
/*
@OPENEMIS SCHOOL LICENSE LAST UPDATED ON 2014-01-30

OpenEMIS School
Open School Management Information System

Copyright � 2014 KORD IT. This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation, 
either version 3 of the License, or any later version. This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please email contact@openemis.org.
*/

App::uses('AppHelper', 'View/Helper');

class LabelHelper extends AppHelper {
	public $messages = array(
		'general' => array(
            'add' => 'Add',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'order' => 'Order',
            'visible' => 'Visible',
            'reorder' => 'Reorder',
            'back' => 'Back',
            'list' => 'List',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'option' => 'Option',
            'description' => 'Description',
            'value' => 'Value',
            'modified' => 'Modified On',
            'created' => 'Created On',
            'type' => 'Type',
            'title' => 'Title',
			'name' => 'Name',
			'date' => 'Date',
			'general' => 'General'
        ),
        'wizard' => array(
            'previous' => 'Previous',
            'next' => 'Next',
            'finish' => 'Finish',
            'skip' => 'Skip',
            'addmore' => 'Add More'
        ),
		'InstitutionSite' => array(
			'institution_site_provider_id' => 'Provider',
			'institution_site_sector_id' => 'Sector'
		),
		'InstitutionSiteCustomField' => array(
			'type' => 'Field Type',
			'institution_site_type_id' => 'Institution Type'
		),
		'InstitutionSiteCustomFieldOption' => array(
			'institution_site_custom_field_id' => 'Custom Field'
		),
        'BankBranch' => array(
            'bank_id' => 'Bank',
            'name' => 'Branch'
        ),
		'InfrastructureMaterial' => array(
			'infrastructure_category_id' => 'Category'
		),
		'InfrastructureStatus' => array(
			'infrastructure_category_id' => 'Category'
		),
		'FinanceType' => array(
			'finance_nature_id' => 'Nature'
		),
		'ContactType' => array(
			'contact_option_id' => 'Contact Option'
		),
        'SchoolYear' => array(
            'name' => 'School Year'
        ),
        'Country' => array(
            'name' => 'Country'
        ),
        'HealthRelationships' => array(
            'name' => 'Relationship'
        ),
        'HealthCondition' => array(
            'name' => 'Condition'
        ),
        'HealthImmunization' => array(
            'name' => 'Immunization'
        ),
        'HealthMedication' => array(
            'start_date' => 'Commenced Date',
            'end_date' => 'Ended Date',
        ),
        'Identities' => array(
            'issue_location' => 'Issuer'
        )
    );
	
	public function get($code) {
		$index = explode('.', $code);
		$message = $this->messages;
		foreach($index as $i) {
			if(isset($message[$i])) {
				$message = $message[$i];
			} else {
				$message = false;
				break;
			}
		}
		return !is_bool($message) ? __($message) : $message;
	}
	
	public function getLabel($model, $obj) {
		$field = $obj['field'];
                $code = $model . '.' . $obj['field'];
		if(isset($obj['labelKey'])) {
                    $code = $obj['labelKey'];
		} else if($field==='modified' || $field==='created') {
                    $code = 'general.'.$obj['field'];
                }
		
		$label = $this->get($code);
		if($label===false) {
			$label = Inflector::humanize($obj['field']);
		}
		return $label;
	}
}
