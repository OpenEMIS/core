<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);
echo $this->element('breadcrumb'); 
$session = $this->Session;
$arrKeys = @array_keys($session->read('InstitutionSite.AdvancedSearch'));
if($arrKeys){
    foreach($arrKeys as $names){ 
        if(strpos($names, "CustomValue") > 0){ 
             $Model = str_replace("CustomValue","",$names);
        }  
     }
}else {
     $Model = "Institution";
}
$preload = @array($Model,(is_null($session->read('InstitutionSite.AdvancedSearch.siteType'))?0:$session->read('InstitutionSite.AdvancedSearch.siteType')));

$this->extend('/Elements/layout/container');

$this->start('contentBody');
?>
<div id="institutions" class="content_wrapper search">
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
		'url' => array('controller' => 'InstitutionSites', 'action' => 'advanced'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('InstitutionSite.AdvancedSearch.Search.area_id')));
	?>
        <h3><?php echo __('General'); ?></h3>
	<div class="row">
		<div class="label"><?php echo __('Area'); ?></div>
		<div class="value"><?php echo $this->Form->input('area', array('id' => 'area', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('InstitutionSite.AdvancedSearch.Search.area'))); ?></div>
	</div>
	
        
        <h3>Custom Fields</h3>
        <?php
        $arrTabs = array('InstitutionSite');
        ?>
        <div class="containerTab">
            <ul class="tabs">
              <?php 
                      foreach ($arrTabs as $tabName){
                          echo '<li '.(($preload[0]==$tabName)?'class="active"':'').' ><a href="#tab1" onClick="objCustomFieldSearch.getDataFields(0,\''.$tabName.'\');">'.__(Inflector::humanize(Inflector::underscore($tabName))).'</a></li>';
                      }
              ?>
            </ul>
            <div class="tab_container">
              <div id="tab1" class="tab_content">
                <div id='CustomFieldDiv'>
                  
                    </div>
              </div>
              
            </div>
        </div>
        <div style="clear:both"></div>
        <div class="controls view_controls">
		<input type="submit" value="<?php echo __('Search'); ?>" class="btn_save btn_right" />
	</div>
	<?php echo $this->Form->end(); ?>
</div>

<script type="text/javascript">
$(document).ready(function() {
    objSearch.attachAutoComplete();
    objCustomFieldSearch.initTabs();
    objCustomFieldSearch.getDataFields(<?php echo $preload[1];?>,'<?php echo $preload[0];?>');
    
})
</script>
<?php $this->end(); ?>