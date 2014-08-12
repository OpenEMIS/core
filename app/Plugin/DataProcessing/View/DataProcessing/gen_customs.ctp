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
				  this.checked = true;         
			 });
		}else{
			c.each(function(){
				 this.checked = false;
			});
		}
		toggleGenerate();

	}
	function toggleSelect(obj) {
		var table = $(obj).closest('.table');

        if(obj.checked) { 
         	table.find('tbody input[type="checkbox"]').each(function() {
                this.checked = true;             
            });
        }else{
           	table.find('tbody input[type="checkbox"]').each(function() {
                this.checked = false;           
            });         
        }
        toggleGenerate();
	}

	function toggleGenerate(){
		if(!$('#btnGenerate').hasClass('backup_running')){
			if($(":checkbox:checked").length>0){
				$('#btnGenerate').removeClass('btn_disabled');
			}else{
				$('#btnGenerate').addClass('btn_disabled');
			}
		}
	}
</script>

<?php
echo $this->Form->create('DataProcessing', array(
	'id' => 'submitForm',
	'inputDefaults' => array('label' => false, 'div' => false),	
	'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
));
echo $this->element('select',array('plugin','DataProcessing'));
foreach($data as $Nav => $arrModules){
?>

	<?php foreach($arrModules as $k => $arrv){ ?>
		
		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<?php if($_execute) { ?>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" value="0" onChange="toggleSelect(this)" /></th>
					<?php } ?>
					<td class="cell-report-name"><?php echo __('Name'); ?></td>
					<td><?php echo __('Description'); ?></td>
				</tr>
			</thead>
			
			<tbody>
				<?php foreach($arrv as $arrValues) {
					$beingProc = 0;
					$chkval = implode(',',array_keys($arrValues['file_kinds']));
					foreach($arrValues['file_kinds'] as $kindsv) {
						foreach($queued as $qK => $qV){
							if(stristr($qV, str_replace(' ','_',$arrValues['name']).'.'.$kindsv)){
								$beingProc = 1;
							}
						}
					}
					$beingProc = ($isBackupRunning) ? 1: $beingProc;
					$arrExtra = array('hiddenField' => false,'type'=>'checkbox','class' => 'icheck-input', 'name'=>'data[Reports][]','value'=>$chkval,'onchange'=>'toggleGenerate();');
					$arrExtra = ($beingProc == 1)?  array_merge($arrExtra,array('disabled'=>'disabled')):$arrExtra;
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
	<?php	
	}
}
?>

<?php if($_execute) { ?>
<div class="controls">
	<input type="submit" id="btnGenerate" value="<?php echo __('Generate'); ?>" class="btn_save <?php echo ($isBackupRunning)?"backup_running":"";?> btn_disabled" />
</div>
<?php } ?>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
