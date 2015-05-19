<?php
$controller = $this->request->params['controller'];
$action = $this->request->params['action'];

$navigations = [];

if ($controller == 'Institutions' && $action == 'index') {
	$navigations = [
		'collapse' => false,
		'items' => [
			'Institutions' => [
				'collapse' => true,
				'url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'index']
			]
		]
	];
} else {
	$navigations = [
		'collapse' => false,
		'items' => [
			'Institutions' => [
				'collapse' => true,
				'url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'index'],
				'items' => [
					'Overview' => ['url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'view']]
				]
			]
		]
	];
}
/*
$navigations = array(
	'collapse' => false,
	'items' => array(
		'General Elements' => array(
			'collapse' => true,
			'items' => array(
				'Typography' => array(
					'collapse' => true,
					'items' => array(
						'Headings' => array('url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'headings')),
						'Lists' => array('url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'lists')),
						'Blockquote' => array('url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'blockquote'))
					)
				),
				'Icons' => array(
					'collapse' => true,
					'items' => array(
						'FontAwesome' => array(
							'url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'fontawesome')
						),
						'KORD IT' => array(
							'url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'kordit')
						)
					)
				),
				'Alerts' => array('url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'alerts'))
			)
		),
		'Panel & Form Elements' => array(
			'collapse' => true,
			'items' => array(
				'Panel' => array('url' => array('plugin' => false, 'controller' => 'Users', 'action' => 'index')),
				'Buttons' => array('url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'buttons'))
			)
		),
		'Customized Theme' => array(
			'collapse' => true,
			'items' => array(
				'Product Theme' => array('url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'productTheme'))
			)
		),
		'Images' => array('url' => array('plugin' => false, 'controller' => 'Main', 'action' => 'images'))
	)
);
*/
?>

<div class="sidebar-nav">
	<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
		<?php echo $this->Navigation->render($navigations) ?>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function() {
	console.log($('.sidebar-nav .nav'));
});
</script>