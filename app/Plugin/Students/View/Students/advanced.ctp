<?php 
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
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
     $Model = "Student";
}
$preload = @array($Model,(is_null($session->read('Institution.AdvancedSearch.siteType'))?0:$session->read('Institution.AdvancedSearch.siteType')));


$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index'), array('class' => 'divider'));
echo $this->Html->link($this->Label->get('general.clear'), array('action' => 'advanced', 0), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Students', 'action' => 'advanced'));
$formOptions['id'] = 'student';
echo $this->Form->create('Search', $formOptions);
echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('Student.AdvancedSearch.Search.area_id')));
echo '<h3>'.__('General').'</h3>';
echo $this->Form->input('area', array('id' => 'area', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('Student.AdvancedSearch.Search.area')));
echo $this->Form->input('identity_type_id', array(
'label' => array('text' => $this->Label->get('Identities.identity'), 'class' => 'col-md-3 control-label'),
'options' => $identityTypeOptions, 'empty'=>'', 'after'=>'</div>'.$this->Form->input('identity', array('type' => 'text', 'div'=>false, 'label'=>false, 'value' => $session->read('Student.AdvancedSearch.Search.identity'))), 'value' => $session->read('Student.AdvancedSearch.Search.identity_type_id')));
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
    objCustomFieldSearch.getDataFields(0,'Student');
    
})
</script> 
<?php
$this->end(); 
?>