<?php 
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/rubrics', false);
//echo $this->Html->script('config', false);
?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'rubricsTemplatesDetailsEdit', $id ), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
    
	<table class='rubric-table'>
    <?php 
		$this->RubricsView->defaultNoOfColumns = $totalColumns;
		//$this->RubricsView->defaultNoOfRows = $totalRows;
		//$options = array('columnHeader'=> $columnHeaderData);
		//pr($options);
		foreach ($this->data as $key =>$item){ 
			if(array_key_exists('RubricsTemplateHeader', $item)){
				echo $this->RubricsView->insertRubricHeader($item, $key, NULL);
			}
			else{
				$item['columnHeader'] = $columnHeaderData;
				echo $this->RubricsView->insertRubricQuestionRow($item , $key, NULL);
			}
		}
	?>
    </table>
</div>
