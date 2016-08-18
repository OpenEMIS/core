<?php
namespace Configuration\Model\Traits;

trait ProductListsTrait {
	public $productTrait = [
		'Core' => [
			'icon' => 'kd-openemis kd-core',
			'name' => 'core'
		],
		'Dashboard' => [
			'icon' => 'kd-openemis kd-dashboard', 
			'name' => 'dashboard'
		],
		'Integrator' => [
			'icon' => 'kd-openemis kd-integrator',
			'name' => 'integrator'
		]
	];
}
