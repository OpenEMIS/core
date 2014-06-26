<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentId', 'site-list');
$this->assign('contentHeader', __('List of Institution Sites'));
$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Code'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Area'); ?></th>
				<th><?php echo __('Site Type'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach($sites as $site):	?>
			<tr>
				<td><?php echo $site['InstitutionSite']['code']; ?></td>
				<td><?php echo $this->Html->link($site['InstitutionSite']['name'], array('controller' => 'InstitutionSites', 'action' => 'index', $site['InstitutionSite']['id'])); ?></td>
				<td><?php echo $site['Area']['name']; ?></td>
				<td><?php echo $site['InstitutionSiteType']['name']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
