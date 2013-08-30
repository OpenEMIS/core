<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

?>

<?php echo $this->element('breadcrumb'); ?>

<style type="text/css">
.cell_name { width: 180px; }
</style>

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
		table.find('.table_body input[type="checkbox"]').each(function() {
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


<div id="reports" class="content_wrapper">
	<?php
	echo $this->Form->create('DataProcessing', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		//'url' => array('controller' => 'DataProcessing', 'action' => 'reports'),
		'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
	));
	?>
	<h1>
		<span><?php echo __('Generate'); ?></span>
		<?php if($_execute) { ?>
		<a class="void divider" href="javascript: void(0);" onclick="turncheckboxes('on')"><?php echo __('Select All'); ?></a>
		<a class="void divider" href="javascript: void(0);" onclick="turncheckboxes('off')"><?php echo __('De-Select All'); ?></a>
		<?php } ?>
	</h1>
	<?php echo $this->element('select',array('plugin','DataProcessing')); ?>
	<?php echo $this->element('alert'); ?>
	<?php
	foreach($data as $Nav => $arrModules){
	?>
		<?php foreach($arrModules as $k => $arrv){ ?>
			
			<div class="table full_width">
				<div class="table_head">
					<?php if($_execute) { ?>
					<div class="table_cell cell_checkbox"><input type="checkbox" value="1" onChange="toggleSelect(this)" /></div>
					<?php } ?>
					<div class="table_cell cell_name"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_desc"><?php echo __('Description'); ?></div>
				</div>
				
				<div class="table_body">
					<?php foreach($arrv as $arrValues){ //pr($arrValues);
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
                                                $arrExtra = array('hiddenField' => false,'type'=>'checkbox','name'=>'data[Reports][]','value'=>$chkval, 'disabled' => (($chkval == '2000' || $chkval == '2001'|| $chkval == '2011')?'':'disabled'));
                                                $arrExtra = ($beingProc == 1)?  array_merge($arrExtra,array('disabled'=>'disabled')):$arrExtra;
					?>
					
					<div class="table_row">
						<?php if($_execute) { ?>
						<div class="table_cell cell_checkbox"><?php echo $this->Form->input('Reports',$arrExtra)?></div>
						<?php } ?>
						<div class="table_cell cell_name"><?php echo __($arrValues['name']); ?></div>
						<div class="table_cell cell_desc"><?php echo __($arrValues['description']); ?></div>
					</div>
					<?php } ?>
				</div>
			</div>
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
</div>