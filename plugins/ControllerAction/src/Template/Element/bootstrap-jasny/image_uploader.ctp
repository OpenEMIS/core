<?php
$width = 90;
$height = 115;
$style = 'width: ' . $width . 'px; height: ' . $height . 'px';
$defaultImageFromHolder = '<img src="'.sprintf('holder.js/%sx%s', $width, $height).'" />';
?>
<script type="text/javascript">
   $(function(){ 
      document.getElementById('removeBtn').onclick = 
          function () { 
              $("#toggleImage").html("<img src=''/>");
          };
   });
</script>
<?php
$tmp_file = ((is_array($data[$attr['field']])) && (file_exists($data[$attr['field']]['tmp_name']))) ? $data[$attr['field']]['tmp_name'] : "";
$tmp_file_read = (!empty($tmp_file)) ? file_get_contents($tmp_file) : ""; 

$src = (!empty($tmp_file_read)) ? '<img src="data:image/jpeg;base64,'.base64_encode( $tmp_file_read ).'"/>' : $defaultImageFromHolder;

if(!is_array($data[$attr['field']])) {
  $imageContent = !is_null($data[$attr['field']]) ? stream_get_contents($data[$attr['field']]) : "";
  $src = (!empty($imageContent)) ? '<img src="data:image/jpeg;base64,'.base64_encode( $imageContent ).'"/>' : $defaultImageFromHolder;
}
?>
<?php header('Content-Type: image/jpeg'); ?>
<div class="input">
  <label for="<?= $attr['field'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr)  ?></label>
  <div class="fileinput fileinput-new" data-provides="fileinput">
    <div class="fileinput-new thumbnail" style="<?= $style ?>" id="toggleImage">
      <?= $src; ?>
    </div>
    <div class="fileinput-preview fileinput-exists thumbnail" style="<?= $style ?>"></div>
    <div style="width: 150px">
      <span class="btn btn-default btn-file">
      <span class="fileinput-new">Select image</span>
      <span class="fileinput-exists">Change</span>
      <input type="file" name="<?= $attr['model'] ?>[<?= $attr['field'] ?>]"></span>
      <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput" id="removeBtn">Remove</a>
    </div>
  </div>
  <?= $this->Form->error($attr['field']) ?>
</div>