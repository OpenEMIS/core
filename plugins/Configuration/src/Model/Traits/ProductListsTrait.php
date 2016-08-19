<?php
namespace Configuration\Model\Traits;

trait ProductListsTrait {
	public $productLists = [
		'OpenEMIS Core' => [
			'icon' => 'kd-openemis kd-core',
			'name' => 'Core'
		],
		'OpenEMIS Dashboard' => [
			'icon' => 'kd-openemis kd-dashboard',
			'name' => 'Dashboard'
		],
		'OpenEMIS Integrator' => [
			'icon' => 'kd-openemis kd-integrator',
			'name' => 'Integrator'
		]
	];
}
