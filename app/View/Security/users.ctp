<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->script('education', false);
echo $this->Html->script('search', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Users'));
$this->start('contentActions');
if($_accessControl->check($this->params['controller'], 'usersAdd')) {
	echo $this->Html->link(__('Add'), array('action' => 'usersAdd'), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'users');
$this->assign('contentClass', 'search');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php 
echo $this->Form->create('SecurityUser', array(
	'url' => array('controller' => 'Security', 'action' => 'users'),
	'inputDefaults' => array('label' => false, 'div' => false)
)); 
?>

<div class="row">
    <div class="search_wrapper">
    	<?php 
			echo $this->Form->input('SearchField', array(
				'id' => 'SearchField',
				//'value' => $searchField,
				'class' => 'default',
				'placeholder' => __('Username, First Name or Last Name')
			));
        ?>
        <span class="icon_clear" onclick="$('#SearchField').val('')">X</span>
    </div>
	<span class="left icon_search" onclick="$('form').submit()"></span>
</div>

<div id="mainlist">
	<?php if(!empty($data)) { ?>
    <div class="row">
        <ul id="pagination">
            <?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
            <?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
            <?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
        </ul>
	</div>
	<?php } ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<td class="table_cell"><?php echo __('Username'); ?></td>
				<td class="table_cell"><?php echo __('First Name'); ?></td>
				<td class="table_cell"><?php echo __('Last Name'); ?></td>
				<td class="table_cell cell_status"><?php echo __('Status'); ?></td>
			</tr>
		</thead>
		
		<tbody class="table_body">
			<?php foreach($data as $user) { 
				$obj = $user['SecurityUser'];
			?>
			<tr class="table_row" row-id="<?php echo $obj['id']; ?>">
				<td class="table_cell"><?php echo $this->Html->link($obj['username'], array('action' => 'usersView', $obj['id']), array('escape' => false)); ?></td>
				<td class="table_cell"><?php echo $obj['first_name']; ?></td>
				<td class="table_cell"><?php echo $obj['last_name']; ?></td>
				<td class="table_cell cell_status"><?php echo $this->Utility->getStatus($obj['status']); ?></td>
			</tr>
			<?php } ?>
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

<?php $this->end(); ?>