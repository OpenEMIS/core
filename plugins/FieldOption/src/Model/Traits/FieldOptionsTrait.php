<?php
namespace FieldOption\Model\Traits;

trait FieldOptionsTrait {
	private $parentFieldOptionList = [
		'FieldOption.BankBranches' => 	['parentModel' => 'FieldOption.Banks', 'foreignKey' => 'bank_id', 'behavior' => 'Filter'], 
		'User.ContactTypes' => 			['parentModel' => 'User.ContactOptions', 'foreignKey' => 'contact_option_id', 'behavior' => 'Filter'], 
		'FieldOption.Banks' => 		['behavior' => 'Display'],
		'FieldOption.Countries' => 	['behavior' => 'Countries'],
		'FieldOption.QualificationSpecialisations' => 	['parentModel' => 'FieldOption.QualificationSpecialisations', 'behavior' => 'QualificationSpecialisations'],
		'FieldOption.QualificationLevels' => 	['behavior' => 'Display'],
	];
}
