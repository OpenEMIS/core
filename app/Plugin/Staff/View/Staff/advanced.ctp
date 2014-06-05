<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);
echo $this->element('breadcrumb'); 
$session = $this->Session;
$arrKeys = @array_keys($session->read('Institution.AdvancedSearch'));
if($arrKeys){
    foreach($arrKeys as $names){ 
        if(strpos($names, "CustomValue") > 0){ 
             $Model = str_replace("CustomValue","",$names);
        }  
     }
}else {
     $Model = "Staff";
}
$preload = @array($Model,(is_null($session->read('Institution.AdvancedSearch.siteType'))?0:$session->read('Institution.AdvancedSearch.siteType')));

?>

<div id="staff" class="content_wrapper search">
	<h1>
		<span><?php echo __('Advanced Search'); ?></span>
		<?php 
		echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
		echo $this->Html->link(__('Clear'), array('action' => 'advanced', 0), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('Search', array(
		'url' => array('controller' => 'Staff', 'action' => 'advanced'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('Staff.AdvancedSearch.Search.area_id')));
	?>
        <h3><?php echo __('General'); ?></h3>
	<div class="row">
		<div class="label"><?php echo __('Area'); ?></div>
		<div class="value"><?php echo $this->Form->input('area', array('id' => 'area', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('Staff.AdvancedSearch.Search.area'))); ?></div>
	</div>
	<h3>Custom Fields</h3>
        <div id='CustomFieldDiv'></div>
         <div style="clear:both"></div>
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Search'); ?>" class="btn_save btn_right" />
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>

<script type="text/javascript">
$(document).ready(function() {
    objSearch.attachAutoComplete();
    objCustomFieldSearch.getDataFields(0,'Staff');
    
})
</script>
*/ ?>
 
<?php 
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$session = $this->Session;
$arrKeys = @array_keys($session->read('Institution.AdvancedSearch'));
if($arrKeys){
    foreach($arrKeys as $names){ 
        if(strpos($names, "CustomValue") > 0){ 
             $Model = str_replace("CustomValue","",$names);
        }  
     }
}else {
     $Model = "Staff";
}
$preload = @array($Model,(is_null($session->read('Institution.AdvancedSearch.siteType'))?0:$session->read('Institution.AdvancedSearch.siteType')));


$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index'), array('class' => 'divider'));
echo $this->Html->link($this->Label->get('general.clear'), array('action' => 'advanced', 0), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Staff', 'action' => 'advanced'));
$formOptions['id'] = 'staff';
echo $this->Form->create('Search', $formOptions);
echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('Staff.AdvancedSearch.Search.area_id')));
echo '<h3>'.__('General').'</h3>';
echo $this->Form->input('area', array('id' => 'area', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('Staff.AdvancedSearch.Search.area')));
echo '<h3>'.__('Custom Fields').'</h3>';
echo '<div id="CustomFieldDiv"></div><div style="clear:both"></div>';
echo '<div class="form-group">';
echo '<div class="col-md-offset-4">';
echo $this->Form->submit($this->Label->get('general.search'), array('class' => 'btn_save btn_right', 'div' => false));
echo '</div>';
echo '</div>';
echo $this->Form->end();
?>
<script type="text/javascript">
$(document).ready(function() {
    objSearch.attachAutoComplete();
    objCustomFieldSearch.getDataFields(0,'Staff');
    
})
</script> 
<?php
$this->end(); 
?>