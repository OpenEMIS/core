<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'index', $templateData['SurveyTemplate']['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell-visible"><?php echo __('Visible'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th class="cell-order"><?php echo __('Order'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>