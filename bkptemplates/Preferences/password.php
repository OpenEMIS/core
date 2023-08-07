<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', 'Preferences');

$this->start('contentBody');

?>

<?= $this->element('preferences_tabs') ?>

<div class="wrapper panel panel-body">

		<!-- Password -->
		<div role="tabpanel" class="tab-pane" id="password">
			<form class="form-horizontal" action="" novalidate="" accept-charset="utf-8" method="post">
				<div class="input string">
					<label>Username</label>	
					<input id="" type="string" disabled="Administrator" maxlength="150" name="Administrator" placeholder="Administrator">				
				</div>
				<div class="input password">
					<label>Password</label>
					<input type="password" id="password" name="password">
				</div>
				<div class="input password">
					<label>New Password</label>
					<input type="password" id="password" name="password">
				</div>
				<div class="input password">
					<label>Retype New Password</label>
					<input type="password" id="password" name="password">
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
</div>

<?php $this->end() ?>

