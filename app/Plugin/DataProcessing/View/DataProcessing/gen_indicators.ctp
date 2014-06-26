<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('DataProcessing.generate'));

$this->start('contentBody');

echo $this->Form->create('DataProcessing', array(
	'id' => 'submitForm',
	'inputDefaults' => array('label' => false, 'div' => false),	
	//'url' => array('controller' => 'DataProcessing', 'action' => 'reports'),
	'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
));

echo $this->element('select',array('plugin','DataProcessing'));

foreach($data as $Nav => $arrModules) :
	foreach($arrModules as $k => $arrv) :
?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<?php if($_execute) { ?>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<?php } ?>
					<td class="cell-report-name"><?php echo __('Name'); ?></td>
					<td><?php echo __('Description'); ?></td>
				</tr>
			</thead>
			
			<tbody>
				<?php foreach($arrv as $arrValues) :
					$beingProc = 0;
					$chkval = implode(',',array_keys($arrValues['file_kinds']));
					foreach($arrValues['file_kinds'] as $kindsv){
						foreach($queued as $qK => $qV){
							if(stristr($qV, str_replace(' ','_',$arrValues['name']).'.'.$kindsv)){
								$beingProc = 1;
							}
						}
					}
					$beingProc = ($isBackupRunning) ? 1: $beingProc;
					$arrExtra = array('hiddenField' => false, 'type'=>'checkbox', 'class' => 'icheck-input', 'name'=>'data[Reports][]','value'=>$chkval);
					$arrExtra = ($beingProc == 1)?  array_merge($arrExtra,array('disabled'=>'disabled')):$arrExtra;
				?>
				
				<tr>
					<?php if($_execute) : ?>
					<td class="checkbox-column"><?php echo $this->Form->input('Reports',$arrExtra)?></td>
					<?php endif ?>
					<td><?php echo __($arrValues['name']); ?></td>
					<td><?php echo __($arrValues['description']); ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		</div>
	<?php endforeach ?>
<?php endforeach ?>

<?php if($_execute) : ?>
<div class="controls">
	<input type="submit" value="<?php echo __('Generate'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>" />
</div>
<?php endif ?>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
