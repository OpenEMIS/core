<?php /*

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="identity" class="content_wrapper">
    <h1>
        <span><?php echo __('Guardians'); ?></span>
        <?php
        if ($_add) {
            echo $this->Html->link(__('Add'), array('action' => 'guardiansAdd'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php echo $this->element('alert'); ?>

    <div class="table allow_hover full_width" action="Students/guardiansView/">
        <div class="table_head">
            <div class="table_cell"><?php echo __('First Name'); ?></div>
            <div class="table_cell"><?php echo __('Last Name'); ?></div>
            <div class="table_cell"><?php echo __('Relationship'); ?></div>
            <div class="table_cell"><?php echo __('Mobile Phone'); ?></div>
        </div>

        <div class="table_body">
            <?php foreach ($list as $obj): ?>
					<div class="table_row" row-id="<?php echo $obj['Guardian']['id']; ?>">
                    <div class="table_cell"><?php echo $obj['Guardian']['first_name']; ?></div>
                    <div class="table_cell"><?php echo $obj['Guardian']['last_name']; ?></div>
                    <div class="table_cell"><?php echo $obj['GuardianRelation']['name']; ?></div>
                    <div class="table_cell"><?php echo $obj['Guardian']['mobile_phone']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
 * 
 * 
 */?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'guardiansAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('First Name'), __('Last Name'), __('Relationship'), __('Mobile Phone'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
        $row[] = $this->Html->link($obj['Guardian']['first_name'], array('action' => 'bankAccountsView', $obj['Guardian']['id']), array('escape' => false));
        $row[] = $obj['Guardian']['last_name'];
        $row[] = $obj['GuardianRelation']['name'] ;
        $row[] = $obj['Guardian']['mobile_phone'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>