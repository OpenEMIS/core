<?php
namespace OpenEmis\Model\Traits;

trait ProductListsTrait {
	private $productList = [
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
