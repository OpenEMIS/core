<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);

$session = $this->Session;

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Advanced Search'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
echo $this->Html->link(__('Clear'), array('action' => 'advanced', 0), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => 'advanced'));
$formOptions['id'] = 'institution';
echo $this->Form->create('Search', $formOptions);
?>

<?php
echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('InstitutionSite.AdvancedSearch.Search.area_id')));
?>
<h3><?php echo __('General'); ?></h3>
<?php echo $this->Form->input('area', array('id' => 'area', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('InstitutionSite.AdvancedSearch.Search.area'))); 
echo $this->Form->input('education_programme_id', array('options' => $educationProgrammeOptions, 'empty'=>'', 'value' => $session->read('InstitutionSite.AdvancedSearch.Search.education_programme_id'))); 

echo '<h3>'.__('Custom Fields').'</h3>';
echo '<div id="CustomFieldDiv"></div><div style="clear:both"></div>';
echo '<div class="form-group">';
echo '<div class="col-md-offset-4">';
echo $this->Form->submit($this->Label->get('general.search'), array('class' => 'btn_save btn_right', 'div' => false));
echo '</div>';
echo '</div>';?>
<?php echo $this->Form->end(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        objSearch.attachAutoComplete();
        objCustomFieldSearch.getDataFields(0,'InstitutionSite');
    })
</script>
<?php $this->end(); ?>