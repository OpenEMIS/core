<?php
$products = [
	'Community' => [
		'icon' => 'kd-openemis kd-community',
		'name' => 'community',
		'url' => ['?' => ['theme' => 'community']]
	],
	'Core' => [
		'icon' => 'kd-openemis kd-core',
		'name' => 'core',
		'url' => ['?' => ['theme' => 'core']]
	],
	'DataManager' => [
		'icon' => 'kd-openemis kd-datamanager',
		'name' => 'datamanager',
		'url' => ['?' => ['theme' => 'data_manager']]
	],
	'Dashboard' => [
		'icon' => 'kd-openemis kd-dashboard', 
		'name' => 'dashboard', 
		'url' => ['?' => ['theme' => 'dashboard']]
	],
	'Identity' => [
		'icon' => 'kd-openemis kd-identity',
		'name' => 'identity',
		'url' => ['?' => ['theme' => 'identity']]
	],
	'Insight' => [
		'icon' => 'kd-openemis kd-insight',
		'name' => 'insight', 
		'url' => ['?' => ['theme' => 'insight']]
	],
	'Integrator' => [
		'icon' => 'kd-openemis kd-integrator',
		'name' => 'integrator',
		'url' => ['?' => ['theme' => 'integrator']]
	],
	'Logistics' => [
		'icon' => 'kd-openemis kd-logistics',
		'name' => 'logistics',
		'url' => ['?' => ['theme' => 'logistics']]
	],
	'Modelling' => [
		'icon' => 'kd-openemis kd-modelling',
		'name' => 'modelling',
		'url' => ['?' => ['theme' => 'modelling']]
	],
	'Monitoring' => [
		'icon' => 'kd-openemis kd-monitoring',
		'name' => 'monitoring',
		'url' => ['?' => ['theme' => 'monitoring']]
	],
	'School' => [
		'icon' => 'kd-openemis kd-school',
		'name' => 'school',
		'url' => ['?' => ['theme' => 'school']]
	],
	'Visualizer' => [
		'icon' => 'kd-openemis kd-visualizer',
		'name' => 'visualizer',
		'url' => ['?' => ['theme' => 'visualizer']]
	]
];
?>

<div class="btn-group">
	<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
		<i class="fa kd-grid"></i>
	</a>

	<div aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu product-lists col-xs-12">
		<div class="dropdown-arrow">
			<i class="fa fa-caret-up"></i>
		</div>

		<div class="product-wrapper">
		<?php foreach ($products as $name => $item) : ?>
			<div class="product-menu col-xs-4">
				<?php 
				$link = '<i class="' . $item['icon'] . '"></i>';
				$link .= '<span>' . $name . '</span>';
				echo $this->Html->link($link, $item['url'], array('escape' => false));
				?>
			</div>		
		<?php endforeach ?>
		</div>
	</div>	
</div>
