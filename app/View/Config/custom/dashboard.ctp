<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('dashboard', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));
?>
<?php if($action == 'index') { ?>
<?php $this->start('contentActions');
if($_add){
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'dashboardAdd'), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody'); ?>
<div class="row select_row form-group">
    <div class="col-md-4">
        <?php
            echo $this->Form->input('type', array(
                'options' => $typeOptions,
                'default' => $selectedType,
                'label' => false,
                'url' => 'Config/index',
                'onchange' => 'jsForm.change(this)',
                'div' => false
            ));
        ?>
    </div>
</div>
	<!-- Items -->
	<?php
		if(isset($items)) {
	?>

		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<td><?php echo __('Active');?></td>
					<td><?php echo __('File');?></td>
					<td><?php echo __('File Type');?></td>
					<td><?php echo __('UploadedOn');?></td>
				</tr>
			</thead>

			<tbody class="table_body">
				<?php 
				foreach($items as $value) {
					$obj = $value[$_model];
					$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
					$ext = array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext;
					$link = $this->Html->link($obj['name'], array('action' => 'dashboardView', $obj['id']));
				?>
				 <tr>
					<td class="table_cell" style="text-align:center;"> 
						<?php if($obj['active'] > 0){ ?>
						<span class="green">✓</span>
						<?php } ?>
					</td>
					<td class="table_cell"><?php echo $link; ?></td>
					<td class="table_cell center"><?php echo ($fileext == 'jpg')? __('JPEG'): strtoupper(__($fileext)); ?></td>
					<td class="table_cell center"><?php echo $obj['created']; ?></td>
				</tr>
				<?php } ?>
				
				
			</tbody>
			</table>
		</div>
		<?php 
		} 
		?>
		<?php $this->end(); ?> 
<?php } ?>