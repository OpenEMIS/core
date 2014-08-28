<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Survey.survey', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Survey/js/survey', false);
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Survey'));
$this->start('contentActions');
if($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'add'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

?>
	
	<?php echo $this->Form->create('Survey',array('url'=>array('controller'=>'Survey','action'=>'index')));?>
    <div class="row">
			<div>
                <div class="left" style="width:300px; padding-left:3px;">
                    <div class="search_wrapper">
                        <?php echo $this->Form->input('Search', array(
							'id' => 'SearchField',
							'value' => $pattern,
							'placeholder' => __("Survey Name"),
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
                    <span class="total"><?php echo $totalfiles; ?> <?php echo __('Survey(s)'); ?></span>
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
	
    <?php echo $this->Form->end(); ?> 
	
	<?php echo $this->element('alert');	?>
    
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<td class="table_cell"><?php echo __('Name'); ?></td>
				<td class="table_cell"><?php echo __('Date'); ?></td>
				<td class="table_cell" style="width:60px;"><?php echo __('Filesize'); ?></td>
				<td class="table_cell" style="width:80px;"><?php echo __('Action'); ?></td>
			</tr>
		</thead>
		<?php
			if(@$data){ 
		?>
		<tbody class="table_body" id="results" cat="">
			<?php foreach($data as $obj) { ?>
			<tr class="table_row <?php //echo $obj['status']==0 ? 'inactive' : ''; ?>" >
				<td class="table_cell mycell"><?php echo str_replace('.json', '',  $obj['basename']); ?></td>
				<td class="table_cell center mycell"><?php echo $obj['time'] ; ?></td>
				<td class="table_cell center mycell"><?php echo $obj['size'] ; ?></td>
                <td class="table_cell center">
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
                </td>
			<?php } ?>
		</tr> 
        <?php } ?>
	</tbody>
</table>
</div>
	    <?php if(sizeof($data)==0) { ?>
            <div class="row center" style="color: red;"><?php echo __('No Survey found.'); ?></div>
        <?php } ?>
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
  <?php echo $this->end(); ?> 