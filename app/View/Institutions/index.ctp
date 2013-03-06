<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false); 
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="institution-list" class="content_wrapper search">
	<h1>
		<span><?php echo __('List of Institutions'); ?></span>
		<span class="divider"></span>
		<span class="total"><?php echo $totalcount;?> <?php echo __('Institutions'); ?></span>
	</h1>
	
	<?php echo $this->element('alert'); ?>
	
	<div class="row">
		<input type="hidden" id="controller" value="Institutions" />
		<input type="hidden" id="action" value="listSites" />
        <?php echo $this->Form->create('Institution', array('action'=>'search','id'=>false)); ?>
		<div class="search_wrapper">
			<?php echo $this->Form->input('SearchField', array(
				'id' => 'SearchField',
				'value' => $searchField,
				'placeholder' => __("Institution Name or Code"),
				'class' => 'default',
				'div' => false,
				'label' => false));
			?>
			<span class="icon_clear">X</span>
		</div>
		<?php echo $this->Js->submit('', array(
			'id'=>'searchbutton',
			'class'=>'icon_search',
			'url'=> $this->Html->url(array('action'=>'index','full_base'=>true)),
			'before'=> "maskId = $.mask({parent: '.search', text:'".__("Searching...")."'});",
			'success'=>'$.unmask({id: maskId, callback: function() { objSearch.callback(data); }});'));
		?>
		<?php echo $this->Form->end(); ?>
	</div>
    <div id="mainlist">
		<div class="row">
			<ul id="pagination">
				<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
				<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
				<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
			</ul>
		</div>
		<div class="table allow_hover">
			<div class="table_head">
				<div class="table_cell cell_code">
					<span class="left"><?php echo __('Code'); ?></span>
					<span class="icon_sort_<?php echo ($sortedcol =='Institution.code')?$sorteddir:'up'; ?>" order="Institution.code"></span>
				</div>
				<div class="table_cell cell_name">
					<span class="left"><?php echo __('Institution Name'); ?></span>
					<span class="icon_sort_<?php echo ($sortedcol =='Institution.name')?$sorteddir:'up'; ?>" order="Institution.name"></span>
				</div>
				<div class="table_cell">
					<span class="left"><?php echo __('Sector'); ?></span>
					<span class="icon_sort_<?php echo ($sortedcol =='InstitutionSector.name')?$sorteddir:'up'; ?>" order="InstitutionSector.name"></span>
				</div>
				<div class="table_cell">
					<span class="left"><?php echo __('Provider'); ?></span>
					<span class="icon_sort_<?php echo ($sortedcol =='InstitutionProvider.name')?$sorteddir:'up'; ?>" order="InstitutionProvider.name"></span>
				</div>
			</div>
			<div class="table_body">
			<?php
			//pr($institutions);
			if(isset($institutions) && count($institutions) > 0){
				$ctr = 1;
				foreach ($institutions as $arrItems):
					//$area = (strlen($arrItems['Area']['name'])>14?substr($arrItems['Area']['name'], 0, 14).'...':$arrItems['Area']['name']);
					$id = $arrItems['Institution']['id'];
					$code = $this->Utility->highlight($searchField,$arrItems['Institution']['code']);
					$name = $this->Utility->highlight($searchField,'<b>'.$arrItems['Institution']['name'].'</b>'.((isset($arrItems['InstitutionHistory']['name']))?'<br>'.$arrItems['InstitutionHistory']['name']:''));
			?>
					<div id="<?php echo $id ?>" class="table_row">
						<div class="table_cell"><?php echo $code; ?></div>
						<div class="table_cell"><?php echo $name; ?></div>
						<div class="table_cell"><?php echo $arrItems['InstitutionSector']['name']; ?></div>
						<div class="table_cell"><?php echo $arrItems['InstitutionProvider']['name']; ?></div>
					</div>
				<?php endforeach;
			}
			?>
			</div>
		</div>
		<?php if(sizeof($institutions)==0) { ?>
		<div class="row center" style="color: red">No Institution found.</div>
		<?php } ?>
		<div class="row">
			<ul id="pagination">
				<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
				<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
				<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
			</ul>
		</div>
    </div> <!-- mainlist end-->
</div>

<?php echo $this->Js->writeBuffer(); ?>