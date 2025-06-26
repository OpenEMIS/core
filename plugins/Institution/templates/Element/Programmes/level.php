<?php
if ($action == 'add') : ?>

<div class="input select required">
    <label><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
    <?php         
        echo $this->Form->select(
            "InstitutionGrades.level", 
            $attr['data'],
            [
                'empty' => '-- Select --',
                'onchange'=>"$('#reload').val('changeEducationGradeId').click();return false;",                           
            ]
        );         
    ?>

</div>

<?php endif ?>
<script type="text/javascript">
$(document).ready(function() {
  // Clone all grade options for later use
  var allGradeOptions = $('[name="grades[education_grade_id]"] option').clone();

  $('[name="InstitutionGrades[level]"]').on('change', function() {
    var selectedProgramme = $(this).val();

    var $gradeSelect = $('[name="grades[education_grade_id]"]');
    // Clear current options
    $gradeSelect.empty();

    // Add default option
    $gradeSelect.append('<option value="">-- Select Grade --</option>');

    // Filter and append options matching the selected programme
    allGradeOptions.each(function() {
      var programme = $(this).data('programme');
      if (programme == selectedProgramme) {
        $gradeSelect.append($(this));
      }
    });

    // If only one option is available, select it automatically
    
  });
});

</script>