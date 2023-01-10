<a id="btn-cancel" href="#" class="btn btn-outline btn-cancel"><i class="fa fa-close"></i> <?= __('Cancel') ?></a>

<script type="text/javascript">
var backHref = document.getElementById('btn-back').getAttribute('href');
document.getElementById('btn-cancel').setAttribute('href', backHref);
</script>
