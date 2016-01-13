<?php
namespace FieldOption\Model\Traits;

trait FieldOptionsTrait {
	private $parentFieldOptionList = [
		'FieldOption.BankBranches' => 	['parentModel' => 'FieldOption.Banks', 'foreignKey' => 'bank_id', 'behavior' => 'Filter'], 
		'User.ContactTypes' => 			['parentModel' => 'User.ContactOptions', 'foreignKey' => 'contact_option_id', 'behavior' => 'Filter'], 
		'FieldOption.Banks' => 		['behavior' => 'Display'],
		'FieldOption.Countries' => 	['behavior' => 'Countries'],
		'Institution.NetworkConnectivities' => 		['behavior' => 'Display'],
		'Health.AllergyTypes' => ['behavior' => 'Display'],
		'Health.ConsultationTypes' => ['behavior' => 'Display'],
		'Health.Relationships' => ['behavior' => 'Display'],
		'Health.Conditions' => ['behavior' => 'Display'],
		'Health.ImmunizationTypes' => ['behavior' => 'Display'],
		'Health.TestTypes' => ['behavior' => 'Display']
	];
}
