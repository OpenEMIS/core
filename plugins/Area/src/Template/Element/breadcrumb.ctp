<?php if (!empty($crumbs)) : ?>
	<?php
		$baseUrl = $this->Url->build([
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action'],
		    'index'
		]);
	?>
	<div class="toolbar-responsive panel-toolbar">
		<ul class="breadcrumb treemap-breadcrumb">
			<?php foreach ($crumbs as $crumb) : ?>
				<?php if ($crumb === end($crumbs)) : ?>
		    		<li class="active"><?= $crumb->name; ?></li>
		    	<?php else : ?>
					<li><a href="<?= $baseUrl . "?parent_id=" . $crumb->id; ?>"><?= $crumb->name; ?></a></li>
				<?php endif ?>
			<?php endforeach ?>
		</ul>
	</div>
<?php endif ?>
