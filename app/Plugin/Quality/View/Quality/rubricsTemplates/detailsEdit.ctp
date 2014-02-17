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
		/*if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'rubricsTemplatesDetails', $id ), array('class' => 'divider'));
		}*/
			echo $this->Html->link(__('Add Heading'), 'javascript:void(0)', array('class' => 'divider', 'onclick'=> 'rubricsTemplate.addHeader('.$id.')'));
			echo $this->Html->link(__('Add Critiria Row'), 'javascript:void(0)', array('class' => 'divider', 'onclick'=> 'rubricsTemplate.addRow('.$id.')'));
			echo $this->Html->link(__('Add Level Column'), array('action' => 'RubricsTemplatesCriteria', $id ), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
    <?php
		$this->RubricsView->defaultNoOfColumns = $totalColumns;
		
		echo $this->Form->create($modelName, array(
			//'url' => array('controller' => 'Quality','plugin'=>'Quality'),
			'type' => 'file',
			'novalidate' => true,
			'inputDefaults' => array('label' => false, 'div'=> array('class'=>'input_wrapper'), 'autocomplete' => 'off')
		));
	?>
    <?php echo $this->Form->hidden('last_id', array('value'=> count($this->data), 'id'=>'last_id')); ?>
	<table class='rubric-table'>
    <?php 
		foreach ($this->data as $key =>$item){ 
			$processItem = array();
			if(array_key_exists('RubricsTemplateHeader', $item)){
				if(!array_key_exists('rubric_template_id', $item['RubricsTemplateHeader'])){
					$processItem['RubricsTemplateHeader']['rubric_template_id'] = $id;
				}
				echo $this->RubricsView->insertRubricHeader($processItem, $key);
			}
			else{
				$processItem['columnHeader'] = $columnHeaderData;
				echo $this->RubricsView->insertRubricQuestionRow($processItem , $key);
			}
		}
	?>
    </table>
    
    <div class="controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'rubricsTemplatesDetailsView', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
    
    <?php echo $this->Form->end(); ?>
</div>
