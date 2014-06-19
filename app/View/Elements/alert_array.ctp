<?php 
$alertTypes = array();
$alertTypes['ok'] = 'alert_ok';
$alertTypes['error'] = 'alert_error';
$alertTypes['info'] = 'alert_info';
$alertTypes['warn'] = 'alert_warn';

if($this->Session->check('_alertArray')) {
	$_alertArray = $this->Session->read('_alertArray');
	$class = 'alert alert_view ' . $alertTypes[$_alertArray['type']];
	$dismiss = $_alertArray['dismissOnClick'] ? sprintf('title="%s"', __('Click to dismiss')) : '';
	unset($_SESSION['_alertArray']);
        
        foreach($_alertArray['messageArray'] AS $alertMsg){
?>

	<div class="<?php echo $class; ?>" <?php echo $dismiss; ?>>
		<div class="alert_icon"></div>
		<div class="alert_content"><?php echo $alertMsg; ?></div>
	</div>

<?php }} ?>