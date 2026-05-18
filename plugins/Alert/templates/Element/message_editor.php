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
</style>

<?php echo $this->Html->script('https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js', ['block' => true]) ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof CKEDITOR !== 'undefined') {
        //Destroy existing instance if already created
        if (CKEDITOR.instances['notices-message']) {
            CKEDITOR.instances['notices-message'].destroy(true);
        }
        CKEDITOR.replace('notices-message', {
            toolbar: [
                { name: 'basicstyles', items: ['Bold','Italic','Underline','Strike'] },
                { name: 'paragraph', items: ['NumberedList','BulletedList','Blockquote'] },
                { name: 'insert', items: ['Link','Image','Table'] },
                { name: 'styles', items: ['Font','FontSize','TextColor','BGColor'] },
                { name: 'align', items: ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },
                { name: 'tools', items: ['Maximize','Source'] }
            ]
        });
    }

});
</script>
