<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<style type="text/css">
.table_wrapper {
	height: 300px;
	width: 640px;
	margin: 8px;
	overflow: auto;
}

.div_table {
	display: table;
	border-collapse: collapse;
	table-layout: fixed;
}

.div_table_row {
	display: table-row;
}

.div_table_row.even {
	background-color: #F7F7F7;
}

.div_table_cell { 
	display: table-cell;
	border: 1px solid #CCCCCC; 
	padding: 4px 8px; 
	font-size: 11px; 
	color: #666666;
}

.div_table .table_head {
	display: table-header-group;
}

.div_table .table_data {
	display: table-row-group;
}

.div_table .foot {
	display: table-footer-group;
}

.div_table .table_head .div_table_cell {
	font-weight: bold; 
	background-color: #EFEFEF;
	border-bottom: 0;
}
</style>

<div id="adhoc-list" class="content_wrapper">
	<h1>
		<span>Ad Hoc Reports</span>
	</h1>
	
	<fieldset class="section_group">
		<legend>Query</legend>
		<?php
		echo $this->Form->create('Report', array(
				'id' => 'submitForm',
				'inputDefaults' => array('label' => false, 'div' => false),	
				'url' => array('controller' => 'Reports', 'action' => 'adhoc')
			)
		);
		?>
		<textarea id="query" name="query" style="width: 653px; height: 200px;"><?php echo $sql; ?></textarea>
		<input type="submit" value="Submit Query" style="margin-top: 10px" />
		<input type="button" value="Select All" style="margin: 10px 0 0 5px;" onclick="$('#query').select()" />
		<?php echo $this->Form->end(); ?>
	</fieldset>
	
	<fieldset class="section_group">
		<legend>Result</legend>
		
		<div class="table_wrapper">
			
			<?php 
			$count=0;
			if(sizeof($result) > 0) { 
				$head = $result['head'];
				$records = $result['records'];
			?>
			<div class="div_table">
				<div class="table_head">
					<?php foreach($head as $title) { ?>
					<div class="div_table_cell"><?php echo $title; ?></div>
					<?php } ?>
				</div>
				
				<div class="table_data">
					<?php foreach($records as $row) { ?>
					<div class="div_table_row<?php echo ++$count%2==0 ? ' even' : '' ?>">
						<?php foreach($row as $val) { ?>
						<div class="div_table_cell"><?php echo $val; ?></div>
						<?php } ?>
					</div>
					<?php } ?>
				</div>
			</div>
			<?php } ?>
		</div>
	</fieldset>
</div>