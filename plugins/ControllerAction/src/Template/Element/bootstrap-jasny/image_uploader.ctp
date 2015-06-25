<script type="text/javascript">
  $(function(){ 

    $('#removeBtn').click(function() {
      $("#removeBtn").css("display", "none");
      
      var image = $("<img>").attr({
        "data-src": "holder.js/90x115"
      })

      Holder.run({
          images: image[0]
      });

      $("#existingImage").remove();
      $("img").remove();
      $("#toggleImage").append(image);

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

  });
</script>
<div class="input string" >
  <label for="<?= $attr['field'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr)  ?></label>
  <div class="fileinput fileinput-new" data-provides="fileinput">
    <div class="fileinput-new thumbnail" style="width: <?= $defaultWidth; ?>px; height: <?= $defaultHeight; ?>px;" id="toggleImage">
      <?= $src ?>
    </div>
    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: <?= $defaultWidth; ?>px; max-height: <?= $defaultHeight; ?>px;"></div>
    <div style="width:200px;">
      <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span><span class="fileinput-exists">Change</span>
      <input type="file" name="<?= $attr['model'] ?>[<?= $attr['field'] ?>]" id="file-input"></span>
      <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput" id="removeBtn">Remove</a>
    </div>
  </div>
  <div style="float:right">
    <?= $this->Form->error($attr['field']) ?>
  </div>
</div>
