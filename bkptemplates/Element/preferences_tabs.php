<div id="tabs" class="nav nav-tabs horizontal-tabs" role="tablist">
	<span role="presentation" class="<?= $selectedTab == 'account' ? 'tab-active' : '' ?>">
		<?= $this->Html->link('Account', ['account']) ?>
	</span>
	<span role="presentation" class="<?= $selectedTab == 'password' ? 'tab-active' : '' ?>">
		<?= $this->Html->link('Password', ['password']) ?>
	</span>
	<span role="presentation" class="<?= $selectedTab == 'setting' ? 'tab-active' : '' ?>">
		<?= $this->Html->link('Setting', ['setting']) ?>
	</span>
</div> 


<script type="text/javascript">
	$(document).ready(function(){
	$('#tabs').scrollTabs();
	});
</script>
