<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="qualificationView" class="content_wrapper">
    <h1>
        <span><?php echo __('Qualification Details'); ?></span>
		<?php
		$data = $teacherQualificationObj[0]['TeacherQualification'];
		echo $this->Html->link(__('List'), array('action' => 'qualifications', $data['teacher_id']), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'qualificationsEdit', $data['id']), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'qualificationsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
		<div class="label"><?php echo __('Level'); ?></div>
		<div class="value"><?php echo $levels[$data['qualification_level_id']]; ?></div>
	</div>

    <div class="row">
        <div class="label"><?php echo __('Institution'); ?></div>
        <div class="value"><?php echo $institutes[$data['qualification_institution_id']]; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Institution Country'); ?></div>
        <div class="value"><?php echo $data['qualification_institution_country']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Qualification Title'); ?></div>
        <div class="value"><?php echo $data['qualification_title']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Major/Specialisation'); ?></div>
        <div class="value"><?php echo $specializations[$data['qualification_specialisation_id']]; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Graduation Year'); ?></div>
        <div class="value"><?php echo $data['graduate_year']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Document No'); ?></div>
        <div class="value"><?php echo $data['document_no']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Grade/Score'); ?></div>
        <div class="value"><?php echo $data['gpa']; ?></div>
    </div>

   <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($teacherQualificationObj[0]['ModifiedUser']['first_name'] . ' ' . $teacherQualificationObj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($teacherQualificationObj[0]['CreatedUser']['first_name'] . ' ' . $teacherQualificationObj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>

    <?php if(!empty($data['file_name'])){ ?>
    <div class="row edit">
        <div class="label"><?php echo __('Attachment'); ?></div>
        <?php 
        $fileext = strtolower(pathinfo($data['file_name'], PATHINFO_EXTENSION));
        $ext = array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext;
        $link = $this->Html->link($data['file_name'], array('action' => 'qualificationAttachmentsDownload', $data['id']));
        ?>
        <div class="value"><?php echo $link; ?></div>
    </div>
    <?php } ?>
    
</div>
