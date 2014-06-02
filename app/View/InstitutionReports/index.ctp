<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
?>

<?php if (count($data) > 0) : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<td><?php echo __('Name'); ?></td>
					<td style="width:100px"><?php echo __('Types'); ?></td> 
				</tr>
			</thead> 
			<tbody>
				<?php foreach ($data as $i => $obj) : ?>
					<tr>
						<td><?php echo __($obj['name']); ?></td>
						<td class="center" style="width:100px;">
							<?php foreach ($obj['formats'] as $name => $action) {
								$url = array('action' => 'generate', $obj['model'], $name);
								if(isset($obj['params']) && isset($obj['params'][$name])) {
									$url[] = $obj['params'][$name];
								}
								echo $this->Html->link(strtoupper($name), $url);
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php
endif;
$this->end();
?>
