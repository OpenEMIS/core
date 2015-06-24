<script type="text/javascript">
   $(function(){ 

          $('#removeBtn').click(function() {
            $("#existingImage").attr('src', 'nothing');
            $("#removeBtn").css("display", "none");
          });

       if($('#existingImage').length) {
         source = $('#existingImage').attr("src");   
         if(source) {
            $('#removeBtn').css('display', 'inline');   
         } else {
            $('#removeBtn').css('display', 'none');   
         }
       }   

   });
</script>
<div class="input string" >
  <label for="<?= $attr['field'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr)  ?></label>
  <div class="fileinput fileinput-new" data-provides="fileinput">
    <div class="fileinput-new thumbnail" style="width: <?= $defaultWidth; ?>px; height: <?= $defaultHeight; ?>px;" id="toggleImage">
      <?= $src; ?>
    </div>
    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: <?= $defaultWidth; ?>px; max-height: <?= $defaultHeight; ?>px;"></div>
    <div style="width:200px;">
      <span class="btn btn-default btn-file"><a class="btn btn-default fileinput-new" id="selectImageBtn">Select image</a><span class="fileinput-exists" id="changeImageBtn">Change</span><input type="file" name="<?= $attr['model'] ?>[<?= $attr['field'] ?>]"></span>
      <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput" id="removeBtn">Remove</a>
    </div>
  </div>
  <div style="float:right">
    <?= $this->Form->error($attr['field']) ?>
  </div>
</div>
