<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'healthAllergyAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr><?php echo $this->Html->tableHeaders(array(__('Type'), __('Description'), __('Severe'), __('Comment'))); ?></tr>
        </thead>
        <tbody>
            <?php
            foreach ($data as $obj) {
                $id = $obj[$model]['id'];
                echo '<tr>			
                            <td>' . $this->Html->link($obj['HealthAllergyType']['name'], array('action' => 'healthAllergyView', $id), array('escape' => false)) . '</td>
                            <td>' . $obj[$model]['description'] . '</td>
                            <td class="center">' . ($obj[$model]['severe'] == 1 ? '&#10003;' : '&#10008;') . '</td>
                            <td>' . $obj[$model]['comment'] . '</td>
                    </tr>';
            }
            ?>
        </tbody>
    </table>
</div>
<?php $this->end(); ?>