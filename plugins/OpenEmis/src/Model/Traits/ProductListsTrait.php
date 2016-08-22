<?php
namespace OpenEmis\Model\Traits;

trait ProductListsTrait {
	private $productLists = [
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

	public function getProductLists($excludedItem = [])
	{
		$productLists = array_diff_key($this->productLists, array_flip($excludedItem));
		return $productLists;
	}
}
