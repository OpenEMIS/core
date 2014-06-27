<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false); 

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'institution-list');
$this->assign('contentClass', 'search');
$this->assign('contentHeader', __('List of Institutions'));
$this->start('contentActions');
$total = 0;
if(strlen($this->Paginator->counter('{:count}')) > 0) {
	$total = $this->Paginator->counter('{:count}');
}
?>
<span class="divider"></span>
<span class="total"><span><?php echo $total ?></span> <?php echo __('Institutions'); ?></span>
<?php
$this->end();

$this->start('contentBody');
?>

<div class="row">
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
	<span class="advanced"><?php echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'link_back')); ?></span>
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
	
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead url="Institutions/index">
				<tr>
					<th>
						<span class="left"><?php echo __('Code'); ?></span>
						<span class="icon_sort_<?php echo ($sortedcol =='Institution.code')?$sorteddir:'up'; ?>" order="Institution.code"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Institution Name'); ?></span>
						<span class="icon_sort_<?php echo ($sortedcol =='Institution.name')?$sorteddir:'up'; ?>" order="Institution.name"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Sector'); ?></span>
						<span class="icon_sort_<?php echo ($sortedcol =='InstitutionSector.name')?$sorteddir:'up'; ?>" order="InstitutionSector.name"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Provider'); ?></span>
						<span class="icon_sort_<?php echo ($sortedcol =='InstitutionProvider.name')?$sorteddir:'up'; ?>" order="InstitutionProvider.name"></span>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ($institutions as $arrItems):
					$id = $arrItems['Institution']['id'];
					$code = $this->Utility->highlight($searchField,$arrItems['Institution']['code']);
					$name = $this->Utility->highlight($searchField,$arrItems['Institution']['name'].((isset($arrItems['InstitutionHistory']['name']))?'<br>'.$arrItems['InstitutionHistory']['name']:''));
			?>
				<tr row-id="<?php echo $id ?>">
					<td><?php echo $code; ?></td>
					<td><?php echo $this->Html->link($name, array('action' => 'listSites', $id), array('escape' => false)); ?></td>
					<td><?php echo $arrItems['InstitutionSector']['name']; ?></td>
					<td><?php echo $arrItems['InstitutionProvider']['name']; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	
	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
</div> <!-- mainlist end-->

<?php echo $this->Js->writeBuffer(); ?>
<?php $this->end(); ?>