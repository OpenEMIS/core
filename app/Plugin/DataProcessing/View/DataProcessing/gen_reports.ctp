<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('DataProcessing.generate'));

$this->start('contentActions');
if($_execute) { ?>
	<a class="void divider" href="javascript: void(0);" onclick="turncheckboxes('on')"><?php echo __('Select All'); ?></a>
	<a class="void divider" href="javascript: void(0);" onclick="turncheckboxes('off')"><?php echo __('De-Select All'); ?></a>
<?php } 
$this->end();

$this->assign('contentId', 'reports');
$this->start('contentBody');
?>

<script type="text/javascript">
	function turncheckboxes(what){
		var  c = $('input[type="checkbox"]');
		if(what == 'on'){
			c.each(function(){
					if( $(this).attr('disabled') == undefined){
						$(this).attr('checked','checked');
					}
			 })
		}else{
			c.removeAttr('checked','checked');
		}

	}
	function toggleSelect(obj) {
		var table = $(obj).closest('.table');
		table.find('tbody input[type="checkbox"]').each(function() {
				if(obj.checked) {
					if( $(this).attr('disabled') == undefined){
						$(this).attr('checked','checked');
					}
				} else {
					$(this).removeAttr('checked');
				}
		});
	}
</script>

<?php
echo $this->Form->create('DataProcessing', array(
	'id' => 'submitForm',
	'inputDefaults' => array('label' => false, 'div' => false),
	'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
));
?>

<?php echo $this->element('select',array('plugin','DataProcessing')); ?>
<?php
foreach($data as $Nav => $arrModules){
?>
	<?php foreach($arrModules as $k => $arrv){ ?>
	<fieldset class="section_group">
		<legend><?php echo __($k); ?></legend>
		
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
				<?php foreach($arrv as $arrValues) { //pr($arrValues);
					$beingProc = 0;
					$chkval = implode(',',array_keys($arrValues['file_kinds']));
					//pr($arrValues);
					foreach($arrValues['file_kinds'] as $kindsv){
						
						
						/*if(in_array(str_replace(' ','_',$arrValues['name']).'.'.$kindsv, $queued) && $beingProc == 0){
						   
							$beingProc = 1;
						}*/
						
						foreach($queued as $qK => $qV){
							if(stristr($qV, str_replace(' ','_',$arrValues['name']).'.'.$kindsv)){
								$beingProc = 1;
							}
						}
					}
					$beingProc = ($isBackupRunning) ? 1: $beingProc;
					$arrExtra = array('hiddenField' => false, 'type'=>'checkbox', 'class' => 'icheck-input', 'name'=>'data[Reports][]','value'=>$chkval);
					$arrExtra = ($beingProc == 1 || $arrValues['enabled'] == 0)?  array_merge($arrExtra,array('disabled'=>'disabled')):$arrExtra;
				?>
				
				<tr>
					<?php if($_execute) { ?>
					<td class="checkbox-column"><?php echo $this->Form->input('Reports',$arrExtra)?></td>
					<?php } ?>
					<td><?php echo __($arrValues['name']); ?></td>
					<td><?php echo __($arrValues['description']); ?></td>
				</tr>
				<?php } ?>
			</tbody>
			</table>
		</div>
	</fieldset>
	<?php	
	}
}
?>

<?php if($_execute) { ?>
<div class="controls">
	<input type="submit" value="<?php echo __('Generate'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>" />
</div>
<?php } ?>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
