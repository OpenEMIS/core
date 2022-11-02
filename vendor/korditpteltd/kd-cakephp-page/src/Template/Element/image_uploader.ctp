<script type="text/javascript">
  $(function(){

    $('#<?= $attr['key'] ?>').click(function() {
      $("#<?= $attr['key'] ?>").css("display", "none");

      $("#existingImage<?= $attr['key'] ?>").remove();
      // $("img").remove();
      $("#toggleImage<?= $attr['key'] ?>").html("<?= $defaultImgView?>");

    });

    if($('#existingImage<?= $attr['key'] ?>').length) {
      source = $('#existingImage<?= $attr['key'] ?>').attr("src");
      btnRemoveDisplay = (source) ? 'inline' : 'none';
      $('#<?= $attr['key'] ?>').css('display', btnRemoveDisplay);
      //change height to max-height and width to max-width
      currentStyle = $("#toggleImage<?= $attr['key'] ?>").attr("style");
      currentStyle = currentStyle.replace('height', 'max-height');
      currentStyle = currentStyle.replace('width', 'max-width');
      $("#toggleImage<?= $attr['key'] ?>").attr("style", currentStyle);
    }

    $('#file-input<?= $attr['key']?>').on('change', function(evt) {
      var file = evt.target.files[0];
      btnShowStatus = (file) ? 'inline' : 'none';
      $('#<?= $attr['key'] ?>').css('display', btnShowStatus);
    });

    $('img').error(function() { $(this).replaceWith( '<h3>Missing Image</h3>' ); });


  });
</script>
<div class="input string" >
  <label for="<?= $attr['key'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['key'] ?></label>
  <div class="fileinput fileinput-new fileinput-preview" data-provides="fileinput">
    <div class="fileinput-new thumbnail" style="width: <?= $defaultWidth; ?>px; height: <?= $defaultHeight; ?>px;" id="toggleImage<?= $attr['key'] ?>">
      <?= $src ?>
      <?php $this->Form->unlockField($attr['attributes']['name']);
      ?>
    </div>
    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: <?= $defaultWidth; ?>px; max-height: <?= $defaultHeight; ?>px;"></div>
      <div class="file-input-buttons">
        <?= !$disabled ? $defaultImgMsg : '' ?>
        <?php if (!$disabled) : ?>
        <span class="btn btn-default btn-file">
          <span class="fileinput-new">
            <i class="fa fa-folder"></i>
            <span><?= __('Select File') ?></span>
          </span>
          <span class="fileinput-exists">
            <i class="fa fa-folder"></i>
            <span><?= __('Change') ?></span>
          </span>
      <?= $this->Form->file($attr['attributes']['name'], [
            'id' => 'file-input-'.$attr['key']
          ])
      ?>
      </span>
          <span class="fileinput-exists"  id="<?= $attr['key'] ?>">
            <a href="#" class="btn btn-default" data-dismiss="fileinput">
              <i class="fa fa-close"></i>
              <span><?= __('Remove') ?></span>
            </a>
          </span>
        <?php endif; ?>
    </div>
  </div>
  <div class="error-message">
    <?= $this->Form->error($attr['key']) ?>
  </div>
</div>
