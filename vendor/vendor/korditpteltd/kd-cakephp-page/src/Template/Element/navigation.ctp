<?php
$menuItemSelected = isset($menuItemSelected) ? implode('-', $menuItemSelected) : '';
$selectedLink = isset($selectedLink) ? $selectedLink : '';
?>

<div class="left-menu">
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        <?php echo $this->Navigation->render() ?>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#accordion').on('show.bs.collapse', function (e) {
        var target = e.target;
        var level = $(target).attr('data-level');
        var id = $(target).attr('id');
        $('[data-level=' + level + ']').each(function() {
            if ($(this).attr('id') != id && $(this).hasClass('in') == true) {
                $(this).collapse('hide');
            }
        });
    })

    var action = '<?= $selectedLink ?>';
    $('#' + action).addClass('nav-active');
    var ul = $('#' + action).parents('ul');

    ul.each(function() {
        $(this).addClass('in');
        $(this).siblings('a.accordion-toggle').removeClass('collapsed');
    });
});
</script>
