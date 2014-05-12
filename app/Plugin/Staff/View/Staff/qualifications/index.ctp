<?php /*
<?php 
echo $this->Html->css('jquery_ui', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Teachers/js/qualifications', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<div id="qualification" class="content_wrapper">

	<h1>
		<span><?php echo __('Qualifications'); ?></span>
		<!-- <a class="divider void" onClick="objTeacherQualifications.show('QualificationAdd')">Add</a> -->
        <?php 
        if($_edit) {
            echo $this->Html->link(__('Add'), array('action' => 'qualificationsAdd'), array('class' => 'divider'));
        }
        ?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/qualificationsView/">
		<div class="table_head" url="Staff/qualifications/">
			<div class="table_cell"><?php echo __('Graduate Year'); ?></div>
			<div class="table_cell"><?php echo __('Level'); ?></div>
			<div class="table_cell"><?php echo __('Qualification Title'); ?></div>
			<div class="table_cell"><?php echo __('Document No.'); ?></div>
			<div class="table_cell"><?php echo __('Insituition'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['id']; ?>">
				<div class="table_cell"><?php echo $obj['graduate_year']; ?></div>
				<div class="table_cell"><?php echo $obj['level']; ?></div>
				<div class="table_cell"><?php echo $obj['qualification_title']; ?></div>
				<div class="table_cell"><?php echo $obj['document_no']; ?></div>
				<div class="table_cell"><?php echo $obj['institute']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>


</div>
 * 
 */?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'qualificationsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Graduate Year'), __('Level'), __('Qualification Title'), __('Document No.'), __('Insituition'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['graduate_year'] ;
	$row[] = $obj['QualificationLevel']['name'] ;
	$row[] = $this->Html->link($obj[$model]['qualification_title'], array('action' => 'qualificationsView', $obj[$model]['id']), array('escape' => false));
	$row[] = $obj[$model]['document_no'] ;
	$row[] = $obj['QualificationInstitution']['name'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>