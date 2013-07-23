<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Survey.survey', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Survey/js/survey', false);
echo $this->Html->css('search', 'stylesheet', array('inline' => false));



?>

<?php echo $this->element('breadcrumb'); ?>
<input type="hidden" id="pageType" value="import">
<div id="users" class="content_wrapper">
	<h1>
		<span><?php echo __('Completed Surveys'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Synchronized'), array('action' => 'synced'), array('class' => 'divider'));
		}
		?>
	</h1>
    
    <div style="display:none;">
	<?php
	
	echo $this->Form->create('Upload', array('type' => 'file'));
	echo $this->Form->input('file.', array('type' => 'file', 'id'=>'fileupload', 'multiple', 'style'=>'hidden'));
	echo $this->Form->end('Upload'); 
	?>
    </div>
    <?php echo $this->Form->create('Survey',array('url'=>array('plugin'=>'Survey','controller'=>'Survey','action'=>'import')));?>
	
	<!--div class="row">
		
		<div class="label">Search</div>
		<div class="value"><?php echo $this->Form->input('Search', array(
				'label' =>false,
				'default' => $pattern
			));  ?>	
		</div>
	</div-->
	
	
		
		
	
	<!--div class="row">
		
		<div class="label">Year</div>
		<div class="value"><?php echo $this->Form->input('Year ', array(
				'id' => 'schoolYear',
				'options' => $years,
				'default' => $selectedYear,
				'onChange' => 'Survey.filterXML();',
				'label' =>false
			));  ?>	
		</div>
	</div>
	<div class="row">
		
		<div class="label">Category</div>
		<div class="value"> 
		echo $this->Form->input('Category ', array(
				'id' => 'category',
				'options' => $category,
				'onChange' => 'Survey.filterXML();',
				'label' =>false
			)); ?>	
		</div>
	</div>
	
	<div class="row">
		
		<div class="label">Type</div>
		<div class="value"><?php echo $this->Form->input('Type ', array(
				'id'=>'siteType',
				'options'=>$sitetypes,
				'onChange' => 'Survey.filterXML();',
				'label' =>false)
			 ); ?>	
		</div>
	</div -->
        
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
                    <span class="icon_clear" onclick="location.href='<?php echo $this->Html->url(array('controller'=>'Survey','action'=>'import'));?>'">X</span>
                </div>
                <?php echo $this->Js->submit('', array(
                    'id'=>'searchbutton',
                    'class'=>'icon_search',
                    'url'=> $this->Html->url(array('action'=>'import','full_base'=>true))));
                ?>
        	</div>
            <div class="left" style="width:375px;">
            	<?php if($totalfiles>0){ ?>
                <span class="total"><?php echo $totalfiles; ?> <?php echo __('Surveys'); ?></span>
                <?php } ?>
        	</div>
		</div>
		<div class="action_pullright">
        	<div class="right" style="padding-left:20px;">
                <div class="left">
                <?php echo $this->Html->image('icons/add.png',array('onclick'=>'$(\'#fileupload\').trigger(\'click\');')); ?> 
                </div>
                <div class="left">
                <?php
                    echo $this->Html->link('&nbsp;&nbsp;' . __('Add'),
                                    '#', array('escape' => false, 'onclick'=>'$(\'#fileupload\').trigger(\'click\');'));
                ?>
                </div>
            </div>
			<div class="right" style="padding-left:20px;">
                <div class="left">
                <?php echo $this->Html->image('icons/sync.png',array('onclick'=>'Survey.massUpdate(\'results\')')); ?> 
                </div>
                <div class="left">
                <?php
                    echo $this->Html->link('&nbsp;&nbsp;' . __('Sync'),
                                    '#', array('escape' => false, 'onclick'=>'Survey.massUpdate(\'results\')'));
                ?>
                </div>
            </div>
            <div class="right" style="padding-left:20px;">
                <div class="left">
                <?php echo $this->Html->image('icons/delete.png',array('onclick'=>'Survey.massDelete(\'results\')')); ?> 
                </div>
                <div class="left">
                <?php
                    echo $this->Html->link('&nbsp;&nbsp;' . __('Delete'),
                                    '#', array('escape' => false, 'onclick'=>'Survey.massDelete(\'results\')'));
                ?>
                </div>
            </div>
			
		</div>
	</div>
	<?php echo $this->element('alert'); ?>
	<div class="table full_width" >
		<div class="table_head">
			<div class="table_cell cell_visible"><?php 
			echo $this->Form->input('checked', array('label'=>false, 'type' => 'checkbox','onchange'=>'Survey.activateSync(this)'));
			?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell cell_time"><?php echo __('Date'); ?></div>
			<div class="table_cell cell_filesize"><?php echo __('Filesize'); ?></div>
			<!--div class="table_cell cell_status"><?php echo __('Status'); ?></div-->
		</div>
        <?php if(@$data){ ?>
		<div class="table_body" id="results" cat="response">
			<?php foreach($data as $obj) { ?>
			<div class="table_row" >
				<div class="table_cell cell_visible"><?php echo $this->Form->input('choices',array('type'=>'checkbox','label'=>false,'value'=>$obj['basename']));?></div>
				<div class="table_cell"><?php echo str_replace('.json', '',  $obj['basename']); ?></div>
				<div class="table_cell cell_time center"><?php echo $obj['time'] ; ?></div>
				<div class="table_cell cell_filesize center"><?php echo $obj['size'] ; ?></div>
			</div>
			<?php } ?>
		</div>
        <?php } ?>
        <?php if(sizeof($data)==0) { ?>
        <div class="row center" style="color: red; width:700px;"><?php echo __('No Survey found.'); ?></div>
        <?php } ?>
	</div>
	<div class="Row">
			<div class="action_pullright">
				<?php 
				
					if(count($data) > 0 ){
						if($firstPage > -1){ echo $this->Html->link(__('First'), array('action' => 'import',0,$pattern), array('class' => 'boxpaginate')); } 
						if($prevPage  > -1){ echo $this->Html->link(__('Prev'), array('action' => 'import',$prevPage,$pattern), array('class' => 'boxpaginate')); } 
						if($nextPage){ echo $this->Html->link(__('Next'), array('action' => 'import',$nextPage,$pattern), array('class' => 'boxpaginate')); } 
						if($lastPage){ echo $this->Html->link(__('Last'), array('action' => 'import',$lastPage,$pattern), array('class' => 'boxpaginate')); } 
					}
				?>
			</div>
		</div>
	

</div>
<script>
    $("#fileupload").change(function() {
        $(this).closest('form').submit();
    });
</script>


