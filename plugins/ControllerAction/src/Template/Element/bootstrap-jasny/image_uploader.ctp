<script type="text/javascript">
  $(function(){ 

    $('#removeBtn').click(function() {
      $("#removeBtn").css("display", "none");

      $("#existingImage").remove();
      $("img").remove();
      $("#toggleImage").html("<?= $defaultImgView?>");

    });

    if($('#existingImage').length) {
      source = $('#existingImage').attr("src");   
      btnRemoveDisplay = (source) ? 'inline' : 'none';
      $('#removeBtn').css('display', btnRemoveDisplay); 
      //change height to max-height and width to max-width
      currentStyle = $("#toggleImage").attr("style");
      currentStyle = currentStyle.replace('height', 'max-height');
      currentStyle = currentStyle.replace('width', 'max-width');
      $("#toggleImage").attr("style", currentStyle);
    }   

    $('#file-input').on('change', function(evt) {
      var file = evt.target.files[0];
      btnShowStatus = (file) ? 'inline' : 'none';
      $('#removeBtn').css('display', btnShowStatus); 
    });

    $('img').error(function() { $(this).replaceWith( '<h3>Missing Image</h3>' ); });


  });
</script>
<div class="input string" >
  <label for="<?= $attr['field'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
  <div class="fileinput fileinput-new fileinput-preview" data-provides="fileinput">
    <div class="fileinput-new thumbnail" style="width: <?= $defaultWidth; ?>px; height: <?= $defaultHeight; ?>px;" id="toggleImage">
      <?= $src ?>
      <?php $this->Form->unlockField($attr['model'].'.'.$attr['field']);
      ?>
    </div>
    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: <?= $defaultWidth; ?>px; max-height: <?= $defaultHeight; ?>px;"></div>
      <div class="file-input-buttons">
        <?= $defaultImgMsg ?>
        <span class="btn btn-default btn-file">
          <span class="fileinput-new">
            <i class="fa fa-folder"></i> 
            <span><?= __('Select File') ?></span>
          </span>
          <span class="fileinput-exists">
            <i class="fa fa-folder"></i> 
            <span><?= __('Change') ?></span>
          </span>
      <?= $this->Form->file($attr['model'].'.'.$attr['field'], [
            'id' => 'file-input'
          ])
      ?>
      </span>
          <span class="fileinput-exists"  id="removeBtn">
            <a href="#" class="btn btn-default" data-dismiss="fileinput">
              <i class="fa fa-close"></i> 
              <span><?= __('Remove') ?></span>
            </a>
          </span>
    </div>
  </div>
  <div class="error-message">
    <?= $this->Form->error($attr['field']) ?>
  </div>
</div>
