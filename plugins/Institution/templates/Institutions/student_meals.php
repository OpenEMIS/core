<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_meals/institution.student.meals.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_meals/institution.student.meals.ctrl', ['block' => true]); ?>

<script>
// Assume you're outputting the session values into a JavaScript object

// Now you can use sessionData to set session storage values in JavaScript
    localStorage.removeItem('institution_id');
    localStorage.removeItem('encoded_url');
    localStorage.removeItem('institutionName');
    localStorage.removeItem('institutionIndexUrl');
    localStorage.removeItem('baseUrl');
    sessionStorage.removeItem('username');
    sessionStorage.removeItem('password');

	sessionStorage.setItem('nbn', '<?php echo $user;?>');
	sessionStorage.setItem('pbn', '<?php echo $pass;?>');
	localStorage.setItem('encoded_url', '<?php echo $meal_url;?>');
    localStorage.setItem('institutionName', '<?php echo $institutionName;?>');
    localStorage.setItem('institution_id', '<?php echo h($institution_id);?>');
    localStorage.setItem('institutionIndexUrl', '<?php echo $institutionIndexUrl;?>');
    localStorage.setItem('baseUrl', '<?php echo $baseUrl;?>');
    localStorage.setItem('baseCoreUrl', '<?php echo $baseCoreUrl;?>'); //POCOR-9633: inject baseCoreUrl so Angular api.service.ts resolves api/v4/ and api/v5/ correctly
</script>

<div>
	<?= $this->element('OpenEmis.breadcrumbs') ?>
    <app-root></app-root>
    <?php
        echo $this->Html->script(BUILD_MAIN);
        echo $this->Html->script(BUILD_POLYFILLS);
        echo $this->Html->script(BUILD_RUNTIME);
        echo $this->Html->script(BUILD_SCRIPTS);
        echo $this->Html->css(STYLE_GUIDE);
    ?>
</div>
