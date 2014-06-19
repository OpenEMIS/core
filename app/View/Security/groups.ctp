<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Groups'));
$this->start('contentActions');
if($_accessControl->check($this->params['controller'], 'groupsAdd')) {
	echo $this->Html->link(__('Add'), array('action' => 'groupsAdd'), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'roles');
$this->assign('contentClass', 'search');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php 
echo $this->Form->create('SecurityGroup', array(
	'url' => array('controller' => 'Security', 'action' => 'groups'),
	'inputDefaults' => array('label' => false, 'div' => false)
)); 
?>
<?php if($groupCount > 15) { ?>
<div class="row">
    <div class="search_wrapper">
    	<?php 
			echo $this->Form->input('SearchField', array(
				'id' => 'SearchField',
				'value' => $searchField,
				'class' => 'default',
				'placeholder' => __('Group Name')
			));
        ?>
        <span class="icon_clear" onclick="$('#SearchField').val('')">X</span>
    </div>
	<span class="left icon_search" onclick="$('form').submit()"></span>
</div>
<?php } ?>

<div id="mainlist">
	<?php if(!empty($data)) { ?>
	<ul id="pagination">
		<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
		<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
		<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
	</ul>
	<?php } ?>
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<td class="table_cell"><?php echo __('Group'); ?></td>
				<td class="table_cell"><?php echo __('No of Users'); ?></td>
			</tr>
		</thead>
		
		<tbody class="table_body">
			<?php foreach($data as $group) {
				$obj = $group['SecurityGroup'];
				$name = $this->Utility->highlight($searchField, $obj['name']);
			?>
			<tr class="table_row" row-id="<?php echo $obj['id']; ?>">
				<td class="table_cell"><?php echo $this->Html->link($name, array('action' => 'groupsView', $obj['id']), array('escape' => false)); ?></td>
				<td class="table_cell"><?php echo $obj['count']; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	</div>
	
	<ul id="pagination">
		<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
		<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
		<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
	</ul>
</div> <!-- mainlist end-->
<?php $this->end(); ?>