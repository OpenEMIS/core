<?php echo $this->element('breadcrumb'); ?>
<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
?>
<div id="shiftsView" class="content_wrapper">
    <h1>
        <span><?php echo __('Shifts'); ?></span>
        <?php
        $dataShift = $shiftObj['InstitutionSiteShift'];
        $dataSchoolYear = $shiftObj['SchoolYear'];
        $dataLocationSite = $shiftObj['InstitutionSite'];
        $dataModifiedUser = $shiftObj['ModifiedUser'];
        $dataCreatedUser = $shiftObj['CreatedUser'];
        
        echo $this->Html->link(__('List'), array('action' => 'shifts'), array('class' => 'divider'));
        if ($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'shiftsEdit', $dataShift['id']), array('class' => 'divider'));
        }
        if ($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'shiftsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <div class="row">
        <div class="label"><?php echo __('Shift Name'); ?></div>
        <div class="value"><?php echo $dataShift['name']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('School Year'); ?></div>
        <div class="value"><?php echo $dataSchoolYear['name']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Start Time'); ?></div>
        <div class="value"><?php echo $dataShift['start_time']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('End Time'); ?></div>
        <div class="value"><?php echo $dataShift['end_time']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Location'); ?></div>
        <div class="value">
            <?php echo $dataLocationSite['name']; ?>
            
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($dataModifiedUser['first_name'] . ' ' . $dataModifiedUser['last_name']); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $dataShift['modified']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($dataCreatedUser['first_name'] . ' ' . $dataCreatedUser['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $dataShift['created']; ?></div>
    </div>
</div>