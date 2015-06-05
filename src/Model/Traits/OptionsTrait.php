<?php
namespace App\Model\Traits;

trait OptionsTrait {
	public function getSelectOptions($code) {
		$options = [
			'general' => [
				'active' => [1 => __('Active'), 0 => __('Inactive')]
			],
			'Staff' => [
				'position_types' => [1 => __('Teaching'), 0 => __('Non-Teaching')]
			]
		];

		$index = explode('.', $code);
		foreach ($index as $i) {
			if (isset($options[$i])) {
				$options = $options[$i];
			} else {
				$options = false;
				break;
			}
		}
		return $options;
	}
}
