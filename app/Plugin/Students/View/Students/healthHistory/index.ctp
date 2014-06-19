<?php /*

<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper">
    <h1>
        <span><?php echo __('Health - History'); ?></span>
        <?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'healthHistoryAdd'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/healthHistoryView/">
        <div class="table_head">
            <div class="table_cell"><?php echo __('Conditions'); ?></div>
            <div class="table_cell"><?php echo __('Current'); ?></div>
            <div class="table_cell"><?php echo __('Comment'); ?></div>
        </div>
        
       
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
                <div class="table_cell"><?php echo $healthConditionsOptions[$val[$modelName]['health_condition_id']]; ?></div>
             	<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($val[$modelName]['current']==1);?></div>
                <div class="table_cell"><?php echo $val[$modelName]['comment'] ?>
                </div>
            </div>
            <?php } ?>
        </div>
        
    </div>
    <?php } ?>
</div> */ ?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'healthHistoryAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array( __('Conditions'), __('Current'), __('Comment'));
$tableData = array();


foreach($data as $obj) {
    $symbol = $this->Utility->checkOrCrossMarker($obj[$model]['current']==1);
	$row = array();
        $row[] = $this->Html->link($obj['HealthCondition']['name'], array('action' => 'healthHistoryView', $obj[$model]['id']), array('escape' => false));
        $row[] = array($symbol, array('class' => 'center')) ;
        $row[] = $obj[$model]['comment'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>