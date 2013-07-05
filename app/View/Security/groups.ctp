<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="roles" class="content_wrapper search">
	<h1>
		<span><?php echo __('Groups'); ?></span>
		<?php
		if($_accessControl->check($this->params['controller'], 'groupsAdd')) {
			echo $this->Html->link(__('Add'), array('action' => 'groupsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
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
	
		<div class="table allow_hover" action="<?php echo $this->params['controller'] . DS . 'groupsView' . DS; ?>">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Group'); ?></div>
				<div class="table_cell"><?php echo __('No of Users'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($data as $group) {
					$obj = $group['SecurityGroup'];
					$name = $this->Utility->highlight($searchField, $obj['name']);
				?>
				<div class="table_row" row-id="<?php echo $obj['id']; ?>">
					<div class="table_cell"><?php echo $name; ?></div>
					<div class="table_cell"><?php echo $obj['count']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
		
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
    </div> <!-- mainlist end-->
</div>