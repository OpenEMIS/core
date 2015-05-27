<?php 
namespace App\Model\Validation;

use Cake\Validation\Validator;

class OtherRules extends Validator {

	public static function tester($value, array $context) {
		pr($value);
		return true;
		// die('validation working');
	}

	// public static function filesize($value, array $context) {
	// 	var_dump(__METHOD__ . ' in ' . __FILE__);
	// 	var_dump(func_get_args());
	// 	exit;
	// }

	// public static function extension($value, array $context) {
	// 	var_dump(__METHOD__ . ' in ' . __FILE__);
	// }
}

?>