<div class="toolbar-responsive panel-toolbar">
	<ul class="breadcrumb treemap-breadcrumb">
	<?php
		array_unshift($crumbs, [
			'name' => __('All'),
			'url' => [
	                'plugin' => 'Institution',
	                'controller' => 'Institutions',
	                'action' => 'InstitutionLands',
	                'institutionId' => $this->request->param('institutionId'),
	                'index'
	            ]
		]);
	?>
		<?php foreach ($crumbs as $crumb) : ?>
			<?php if ($crumb === end($crumbs)) : ?>
				<li class="active"><?= $crumb['name']; ?></li>
	    	<?php else : ?>
				<li><?= $this->Html->link($crumb['name'], $crumb['url'])?></li>
			<?php endif ?>
		<?php endforeach ?>
	</ul>
</div>
