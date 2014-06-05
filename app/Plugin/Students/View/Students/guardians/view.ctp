<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="identityView" class="content_wrapper">
    <h1>
        <span><?php echo __('Guardians'); ?></span>
        <?php
        $dataGuardian = $guardianObj['Guardian'];
        $dataStudentGuardian = $guardianObj['StudentGuardian'];
        $dataRelationship = $guardianObj['GuardianRelation'];
        $dataEducation = $guardianObj['GuardianEducationLevel'];
        $dataModifiedUser = $guardianObj['ModifiedUser'];
        $dataCreatedUser = $guardianObj['CreatedUser'];
        echo $this->Html->link(__('List'), array('action' => 'guardians', $dataStudentGuardian['student_id']), array('class' => 'divider'));
        if ($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'guardiansEdit', $dataGuardian['id']), array('class' => 'divider'));
        }
        if ($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'guardiansDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <div class="row">
        <div class="label"><?php echo __('Relationship'); ?></div>
        <div class="value"><?php echo $dataRelationship['name']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('First Name'); ?></div>
        <div class="value"><?php echo $dataGuardian['first_name']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Last Name'); ?></div>
        <div class="value"><?php echo $dataGuardian['last_name']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Gender'); ?></div>
        <div class="value"><?php echo $this->Utility->formatGender($dataGuardian['gender']); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Mobile Phone'); ?></div>
        <div class="value"><?php echo $dataGuardian['mobile_phone']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Home Phone'); ?></div>
        <div class="value"><?php echo $dataGuardian['home_phone']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Office Phone'); ?></div>
        <div class="value"><?php echo $dataGuardian['office_phone']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Email'); ?></div>
        <div class="value"><?php echo $dataGuardian['email']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Address'); ?></div>
        <div class="value"><?php echo nl2br($dataGuardian['address']); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Postal Code'); ?></div>
        <div class="value"><?php echo $dataGuardian['postal_code']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Occupation'); ?></div>
        <div class="value"><?php echo $dataGuardian['occupation']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Education Level'); ?></div>
        <div class="value"><?php echo $dataEducation['name']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo nl2br($dataGuardian['comments']); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($dataModifiedUser['first_name'] . ' ' . $dataModifiedUser['last_name']); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $dataGuardian['modified']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($dataCreatedUser['first_name'] . ' ' . $dataCreatedUser['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $dataGuardian['created']; ?></div>
    </div>
</div>
