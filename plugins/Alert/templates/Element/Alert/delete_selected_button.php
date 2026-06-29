<?php
/**
 * Delete Selected bulk action button
 */
$id = $id ?? 'delete-selected-btn';
$classes = $classes ?? 'btn btn-info';
$disabled = !empty($disabled);
?>
<button type="button"
    id="<?= h($id) ?>"
    class="<?= h($classes) ?>"
    <?= $disabled ? 'disabled' : '' ?>>
    <i class="fa fa-trash"></i>
</button>
<?php
// End of block not needed
