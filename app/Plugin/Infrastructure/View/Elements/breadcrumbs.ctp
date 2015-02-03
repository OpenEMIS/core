<?php 
if(empty($rootUrl)){
	$rootUrl = array('controller' => 'InfrastructureLevel', 'action' => 'index', 'plugin' => false);
}

if(empty($rootName)){
	$rootName = __('All');
}

?>
<?php if (!empty($breadcrumbs)): ?>
	<ul class="breadcrumb">
		<?php
		echo '<li>' . $this->Html->link($rootName, $rootUrl) . '</li>';
		foreach ($breadcrumbs as $i => $item) {
			if ($i == count($breadcrumbs) - 1) {
				echo '<li class="active">' . $item['name'] . '</li>';
			} else {
				echo '<li>' . $this->Html->link($item['name'], array('action' => 'index', 'parent_id' => $item['id'])) . '</li>';
			}
		}
		?>
	</ul>
<?php endif; ?>