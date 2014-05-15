<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->script('education', false);
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="users" class="content_wrapper search">
	<h1>
		<span><?php echo __('Users'); ?></span>
		<?php
		if($_accessControl->check($this->params['controller'], 'usersAdd')) {
			echo $this->Html->link(__('Add'), array('action' => 'usersAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
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
	
		<div class="table allow_hover" action="<?php echo $this->params['controller'] . DS . 'usersView' . DS; ?>">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Username'); ?></div>
				<div class="table_cell"><?php echo __('First Name'); ?></div>
				<div class="table_cell"><?php echo __('Last Name'); ?></div>
				<div class="table_cell cell_status"><?php echo __('Status'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($data as $user) { 
					$obj = $user['SecurityUser'];
				?>
				<div class="table_row" row-id="<?php echo $obj['id']; ?>">
					<div class="table_cell"><?php echo $obj['username']; ?></div>
					<div class="table_cell"><?php echo $obj['first_name']; ?></div>
					<div class="table_cell"><?php echo $obj['last_name']; ?></div>
					<div class="table_cell cell_status"><?php echo $this->Utility->getStatus($obj['status']); ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
		
		<div class="row">
			<ul id="pagination">
				<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
				<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
				<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
			</ul>
		</div>
    </div> <!-- mainlist end-->
</div>