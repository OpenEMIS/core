<?php
$products = [
	'Dashboard' => [
		'icon' => 'kd-openemis kd-dashboard', 
		'name' => 'dashboard', 
		'url' => ['?' => ['theme' => 'dashboard']]
	],
	'Survey' => [
		'icon' => 'kd-openemis kd-survey', 
		'name' => 'survey', 
		'url' => ['?' => ['theme' => 'survey']]
	],
	'Logistics' => [
		'icon' => 'kd-openemis kd-logistics',
		'name' => 'logistics',
		'url' => ['?' => ['theme' => 'logistics']]
	],
	'Integrator' => [
		'icon' => 'kd-openemis kd-integrator',
		'name' => 'integrator',
		'url' => ['?' => ['theme' => 'integrator']]
	],
	'Insight' => [
		'icon' => 'kd-openemis kd-insight',
		'name' => 'insight', 
		'url' => ['?' => ['theme' => 'insight']]
	],
	'School' => [
		'icon' => 'kd-openemis kd-school',
		'name' => 'school',
		'url' => ['?' => ['theme' => 'school']]
	],
	'Core' => [
		'icon' => 'kd-openemis kd-core',
		'name' => 'core',
		'url' => ['?' => ['theme' => 'core']]
	],
	'Monitoring' => [
		'icon' => 'kd-openemis kd-monitoring',
		'name' => 'monitoring',
		'url' => ['?' => ['theme' => 'monitoring']]
	],
	'Visualizer' => [
		'icon' => 'kd-openemis kd-visualizer',
		'name' => 'visualizer',
		'url' => ['?' => ['theme' => 'visualizer']]
	],
	'Purple' => [
		'icon' => 'kd-openemis kd-purple',
		'name' => 'purple',
		'url' => ['?' => ['theme' => 'purple']]
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
