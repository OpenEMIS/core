<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Survey.survey', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Survey/js/survey', false);
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper">
	<h1>
		<span><?php echo __('New Surveys'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'add'), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php echo $this->Form->create('Survey',array('url'=>array('plugin'=>'Survey','controller'=>'Survey','action'=>'index')));?>
    <div class="row">
			<div>
                <div class="left" style="width:300px;">
                    <div class="search_wrapper">
                        <?php echo $this->Form->input('Search', array(
							'id' => 'SearchField',
							'value' => $pattern,
							'placeholder' => __("Filename"),
							'class' => 'default',
							'div' => false,
							'label' => false));
						?>
                        <span class="icon_clear" onclick="location.href='<?php echo $this->Html->url(array('controller'=>'Survey','action'=>'index'));?>'">X</span>
                    </div>
                    <?php echo $this->Js->submit('', array(
						'id'=>'searchbutton',
						'class'=>'icon_search',
						'url'=> $this->Html->url(array('action'=>'index','full_base'=>true))));
					?>
                </div>
                <div class="left" style="width:375px;">
                	<?php if($totalfiles>0){ ?>
                    <span class="total"><?php echo $totalfiles; ?> <?php echo __('Surveys'); ?></span>
                    <?php } ?>
                </div>
			</div>
            </div>
    <!--
	<div class="row">
		
		<div class="label">Search</div>
		<div class="value"><?php echo $this->Form->input('Search', array(
				'label' =>false,
				'default' => $pattern
			));  ?>	
		</div>
	</div>-->
	
    <?php echo $this->end(); ?> 
	
	<?php echo $this->element('alert');	?>
    
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell" style="width:60px;"><?php echo __('Filesize'); ?></div>
			<div class="table_cell" style="width:40px;"><?php echo __('Action'); ?></div>
		</div>
		<?php
			if(@$data){ 
		?>
		<div class="table_body" id="results" cat="">
			<?php foreach($data as $obj) { ?>
			<div class="table_row <?php //echo $obj['status']==0 ? 'inactive' : ''; ?>" >
				<div class="table_cell mycell"><?php echo str_replace('.json', '',  $obj['basename']); ?></div>
				<div class="table_cell center mycell"><?php echo $obj['time'] ; ?></div>
				<div class="table_cell center mycell"><?php echo $obj['size'] ; ?></div>
                <div class="table_cell center">
                <?php /*echo $this->Html->link('Edit', array(
					'controller' => 'Survey',
					'action' => 'edit',
					$obj['basename'])
					);*/
					echo $this->Html->image("icons/edit.png", array(
						"alt" => "Edit",
						'url' => array(
									'controller' => 'Survey',
									'action' => 'edit',
									$obj['basename']
								)
					));
				?>
				<?php /* echo $this->Html->link('Download', array(
					'controller' => 'Survey',
					'action' => 'download',
					$obj['basename'])
					);*/
					echo $this->Html->image("icons/download.png", array(
						"alt" => "Download",
						'url' => array(
									'controller' => 'Survey',
									'action' => 'download',
									$obj['basename']
								)
					));
				?>
				<?php /*echo $this->Html->link('Delete', array(
					'controller' => 'Survey',
					'action' => 'delete',
					'?' => array('file' => $obj['basename']))
					);*/
					echo $this->Html->image("icons/delete.png", array(
						"alt" => "Delete",
						'url' => array(
								'controller' => 'Survey',
								'action' => 'delete',
								'?' => array('file' => $obj['basename']))
					));
				?>	
                </div>
			</div>
			<?php } ?>
		</div> 
        <?php } ?>
		<?php if(sizeof($data)==0) { ?>
        <div class="row center" style="color: red; width:700px;"><?php echo __('No Survey found.'); ?></div>
        <?php } ?>
	</div>
		<?php
			if(@$data){ 
		?>
		<div class="Row">
			<div class="action_pullright">
				<?php 
				
					if(count($data) > 0 ){
						if($firstPage > -1){ echo $this->Html->link(__('First'), array('action' => 'index',0,$pattern), array('class' => 'boxpaginate')); } 
						if($prevPage  > -1){ echo $this->Html->link(__('Prev'), array('action' => 'index',$prevPage,$pattern), array('class' => 'boxpaginate')); } 
						if($nextPage){ echo $this->Html->link(__('Next'), array('action' => 'index',$nextPage,$pattern), array('class' => 'boxpaginate')); } 
						if($lastPage){ echo $this->Html->link(__('Last'), array('action' => 'index',$lastPage,$pattern), array('class' => 'boxpaginate')); } 
					}
				?>
			</div>
		</div>
    	<?php } ?>
</div>