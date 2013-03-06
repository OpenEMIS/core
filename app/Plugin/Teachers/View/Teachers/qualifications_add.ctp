<?php
$index = $order;
// $fieldName = sprintf('data[Training][%s][%%s]', $index);
?>

<div data-id="<?php echo $index; ?>" class="table_row new_row <?php echo $order%2==0 ? 'even' : ''; ?>">
    <div class="table_cell">
        <input type="hidden" value="0" name="data[TeacherQualification][<?php echo $index;?>][id]" />
        <?php echo $this->Utility->getDatePicker($this->Form, 'issue_date', array('name' => "data[TeacherQualification][".$index."][issue_date]", 'desc' => true)); ?>
    </div>
    <div class="table_cell">
        <select id="certificates" class="full_width" name="data[TeacherQualification][<?php echo $index;?>][teacher_qualification_certificate_id]">
        <option value="0">--Select--</option> 
        <?php foreach($certificates as $certificate): ?>
            <option value="<?php echo $certificate['TeacherQualificationCertificate']['id']; ?>"><?php echo $certificate['TeacherQualificationCertificate']['name']; ?></option>
        <?php endforeach; ?>
        </select>
    </div>
    <div class="table_cell"><div class="input_wrapper"><input type="text" name="data[TeacherQualification][<?php echo $index;?>][certificate_no]" value="" /></div></div>
    <div class="table_cell">
        <select class="full_width" name="data[TeacherQualification][<?php echo $index;?>][teacher_qualification_institution_id]">
        <option value="0">--Select--</option> 
        <?php foreach($institutes as $institute): ?>
            <option value="<?php echo $institute['TeacherQualificationInstitution']['id']; ?>"><?php echo $institute['TeacherQualificationInstitution']['name']; ?></option>
        <?php endforeach; ?>
        </select>
    </div>
    <div class="table_cell">
        <span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="objTeacherQualifications.removeRow(this)"></span>
    </div>
</div>