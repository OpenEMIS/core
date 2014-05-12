<?php /*

<?php echo $this->element('breadcrumb'); ?>
<?php 
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('guardian', false);
echo $this->Html->script('app.date', false);
?>
<div id="guardians" class="content_wrapper edit add">
    <h1>
        <span><?php echo __('Guardians'); ?></span>
        <?php
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'guardians'), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    echo $this->Form->create('Guardian', array(
        'url' => array('controller' => 'Students', 'action' => 'guardiansAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php $obj = @$this->request->data; ?>
    <?php echo $this->Form->input('Guardian.existing_id', array('type' => 'hidden', 'value' => isset($obj['Guardian']['existing_id']) ? $obj['Guardian']['existing_id'] : 0)); ?>
    <div class="row">
        <div class="label"><?php echo __('Search'); ?></div>
        <div class="value"><?php echo $this->Form->input('Search.guardian_name'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Relationship'); ?></div>
        <div class="value"><?php echo $this->Form->input('StudentGuardian.guardian_relation_id', array('empty' => __('--Select--'), 'options' => $relationshipOptions)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('First Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.first_name'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Last Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.last_name'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Gender'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.gender', array('empty' => __('--Select--'), 'options' => $genderOptions)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Mobile Phone'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.mobile_phone'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Home Phone'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.home_phone'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Office Phone'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.office_phone'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Email'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.email'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Address'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.address', array('type' => 'textarea')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Postal Code'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.postal_code'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Occupation'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.occupation'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Education Level'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.guardian_education_level_id', array('empty' => __('--Select--'), 'options' => $educationOptions)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $this->Form->input('Guardian.comments', array('type' => 'textarea')); ?></div>
    </div>
    <div class="controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'guardians'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>
 * */ 
 ?>

<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('guardian', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'guardians'), array('class' => 'divider'));
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'guardiansAdd'));
echo $this->Form->create($model, $formOptions);

$obj = $this->request->data; 
echo $this->Form->input('Guardian.existing_id', array('type' => 'hidden', 'value' => isset($obj['Guardian']['existing_id']) ? $obj['Guardian']['existing_id'] : 0));
echo $this->Form->input('Search.guardian_name');
echo $this->Form->input('StudentGuardian.guardian_relation_id', array('options' => $relationshipOptions));
echo $this->Form->input('Guardian.first_name'); 
echo $this->Form->input('Guardian.last_name');
echo $this->Form->input('Guardian.gender', array('options' => $genderOptions));
echo $this->Form->input('Guardian.mobile_phone');
echo $this->Form->input('Guardian.office_phone'); 
echo $this->Form->input('Guardian.email'); 
echo $this->Form->input('Guardian.address', array('type' => 'textarea'));
echo $this->Form->input('Guardian.postal_code');
echo $this->Form->input('Guardian.guardian_education_level_id', array('options' => $educationOptions));
echo $this->Form->input('Guardian.comments', array('type' => 'textarea'));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'guardians')));
echo $this->Form->end();

$this->end();
?>
