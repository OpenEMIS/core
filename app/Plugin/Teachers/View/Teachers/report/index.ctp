<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>
<?php if(count($data)>0) { ?>
<div class="content_wrapper">
       <?php foreach($data as $module => $arrVals) {   ?>
        <h1><span><?php echo __(ucwords($module)); ?></span></h1>
        <div class="table  full_width" action="Teachers/<?php echo $this->action;?>/">
            <div class="table_head">
				<div class="table_cell col_name"><?php echo __('Name'); ?></div>
				<div class="table_cell" style="width:100px"><?php echo __('Types'); ?></div> 
            </div> 
            <div class="table_body">
            <?php foreach($arrVals as $arrTypVals) { ?>
                <div class="table_row" row-id="<?php echo $arrTypVals['name']; ?>">
					<div class="table_cell col_name"><?php echo __($arrTypVals['name']);?></div>
					<div class="table_cell"  style="width:100px;text-align: center">
						<?php foreach($arrTypVals['types'] as $val){?>
						   <?php 
						   echo $this->Html->link( __($val),
								array('action' =>'reportGen', $arrTypVals['name'], $val));
						   }?>
					</div>
                </div>
            <?php } ?>
            </div>
       </div>
        <?php } ?>
</div>
<?php } ?>