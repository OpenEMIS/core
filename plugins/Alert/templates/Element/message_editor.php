<!--POCOR-9558-->
<style>
    div#cke_notifications_area_notices-message{
        display: none;
    }

    #cke_notices-message  {
        float: right;
        width: 81%;
        margin-bottom: 25px;
    }

    #notices-message {
        visibility: hidden;
        display: none;
        float: left;
    }
    div#cke_105_uiElement {
        display: none;
    }
    select.cke_dialog_ui_input_select{
        height: 32%;
    }
</style>

<?php echo $this->Html->script('https://cdn.ckeditor.com/4.22.1/full/ckeditor.js', ['block' => true]) ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof CKEDITOR !== 'undefined') {
        //Destroy existing instance if already created
        if (CKEDITOR.instances['notices-message']) {
            CKEDITOR.instances['notices-message'].destroy(true);
        }
        CKEDITOR.replace('notices-message', {
            toolbar: [
                { name: 'basicstyles', items: ['Bold','Italic','Underline'] },
                { name: 'styles', items: ['Font','FontSize','TextColor','BGColor'] },
                { name: 'align', items: ['JustifyLeft','JustifyCenter','JustifyRight'] },
                { name: 'paragraph', items: ['NumberedList','BulletedList'] },
                { name: 'insert', items: ['Link','Image'] },
               // { name: 'tools', items: ['Source'] }
            ]
        });
    }

});
</script>
