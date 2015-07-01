<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', 'Preferences');

$this->start('contentBody');

?>

<?= $this->element('preferences_tabs') ?>

<div class="wrapper panel panel-body">

		<!-- Setting -->
		<div role="tabpanel" class="tab-pane" id="password">
			<form class="form-horizontal" action="" novalidate="" accept-charset="utf-8" method="post">
				<div class="input">
					<label class="change-size">Breadcrumb</label>
				    <div id="breadcrumb" class="font-toggle btn-group">
						<button id='show' class="btn btn-default" type="button"><span>Show</span></button>
						<button id='hide' class="btn btn-default" type="button"><span>Hide</span></button>
					</div>
				</div>
				<div class="input">
					<label>Font Size</label>
				    <div id="size" class="font-toggle btn-group">
						<button id='small' class="btn btn-default" type="button"><span>Small</span></button>
						<button id='medium' class="btn btn-default" type="button"><span>Medium</span></button>
						<button id='large' class="btn btn-default" type="button"><span>Large</span></button>
					</div>
				</div>
				<div class="input select">
					<label>Language</label>
					<div class="input-select-wrapper">
						<select id="system-language" onchange="$('#reload').click()" name="System[language]">
							<option value="ar">العربية</option>
							<option value="zh">中文</option>
							<option selected="selected" value="en">English</option>
							<option value="fr">Français</option>
							<option value="ru">русский</option>
							<option value="es">español</option>
						</select>
					</div>	
				</div>
				<div class="form-buttons">
					<div class="button-label"></div>
					<button class="hidden" value="reload" name="submit" type="submit" id="reload">reload</button>
					<button type="submit" class="btn btn-default btn-save">Save</button>
					<a class="btn btn-outline btn-cancel" href="">Cancel</a>
				</div>
			</form>
		</div>

</div>


<script type="text/javascript">
	$('#small').click(function(){
    	console.log('clicked');
	    $('body').removeClass('medium').addClass('small');
	    $('body').removeClass('large').addClass('small');
	});

	$('#medium').click(function(){
	    $('body').removeClass('small').addClass('medium');
	    $('body').removeClass('large').addClass('medium');
	});

	$('#large').click(function(){
	    $('body').removeClass('small').addClass('large');
	    $('body').removeClass('medium').addClass('large');
	});

	$('#show').click(function(){
	    $('.breadcrumb').removeClass('hide').addClass('show');
	});

	$('#hide').click(function(){
	    $('.breadcrumb').removeClass('show').addClass('hide');
	});


	$(".btn").click(function(){
	  if (!$(this).hasClass("selected-size")) {
	    $(".btn.selected-size").removeClass("selected-size");
	    $(this).addClass("selected-size");
	  }
	});


</script>

<?php $this->end() ?>

