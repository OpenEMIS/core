<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'rubricsTemplatesAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>

<?php echo $this->element('alert'); ?>
<?php if(isset($data)) { ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered" action="<?php echo $this->params['controller'];?>/rubricsTemplatesView/">
    <thead class="table_head">
   		<tr>
            <td class="table_cell"><?php echo __('Name'); ?></td>
            <td class="table_cell"><?php echo __('Description'); ?></td>
            <td class='cell_status'><?php echo __('Action') ?></td>
        </tr>
    </thead>
   
    <tbody class="table_body">
    	<?php foreach($data as $id=>$val) { ?>
        <tr class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
        	<td class="table_cell"><?php echo $this->Html->link('<div>'.$val[$modelName]['name'].'</div>', array('action' => 'rubricsTemplatesHeader', $val[$modelName]['id']), array('escape' => false));?></td>
            <td class="table_cell"><?php echo $val[$modelName]['description']; ?></td>
            <td class="table_cell cell_status"><?php echo $this->Html->link('<div>'.__('View Details').'</div>', array('action' => 'rubricsTemplatesView', $val[$modelName]['id']), array('escape' => false)); ?></td>
        </tr>
       <?php } ?>
    </tbody>
</table>
</div>
<?php } ?>
<?php $this->end(); ?>  