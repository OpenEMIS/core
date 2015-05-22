<?php
$navigations = [];

if (isset($_navigations)) {
	$navigations = $_navigations;
} else {
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
}
?>

<div class="sidebar-nav">
	<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
		<?php echo $this->Navigation->render($navigations) ?>
	</div>
</div>
