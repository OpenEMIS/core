<?php
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Groups'));
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
}
$this->end();
$this->assign('contentClass', 'search');

$this->start('contentBody');
echo $this->Form->create($model, array(
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

<?php if(!empty($data)) { ?>
<ul id="pagination">
	<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
	<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
	<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
</ul>
<?php } ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<td><?php echo __('Group'); ?></td>
				<td><?php echo __('No of Users'); ?></td>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach($data as $group) :
				$obj = $group['SecurityGroup'];
				$name = $this->Utility->highlight($searchField, $obj['name']);
			?>
			<tr>
				<td><?php echo $this->Html->link($name, array('action' => $model, 'view', $obj['id'])); ?></td>
				<td><?php echo $obj['count']; ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<ul id="pagination">
	<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
	<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
	<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
</ul>
<?php $this->end(); ?>
