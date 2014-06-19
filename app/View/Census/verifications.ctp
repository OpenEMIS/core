<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Verifications'));

$this->start('contentActions');
if ($_execute) {
    if ($allowVerify) {
        echo $this->Html->link(__('Verify'), array('action' => 'verifies', 1), array('class' => 'divider', 'onclick' => 'return Census.verify(this, "GET")'));
    }
    if ($allowUnverify) {
        echo $this->Html->link(__('Unverify'), array('action' => 'verifies', 0), array('class' => 'divider', 'onclick' => 'return Census.verify(this, "GET")'));
    }
}
$this->end();

$this->start('contentBody');
?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th><?php echo __('Year'); ?></th>
                <th><?php echo __('By'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Status'); ?></th>
            </tr>
        </thead>

        <tbody>
            <?php
            $bold = '<b>%s</b>';
            $counter = count($data) > 0 ? $data[0]['SchoolYear']['name'] : null;
            for ($i = 0; $i < count($data); $i++) {
                $highlight = '';
                $obj = $data[$i];
                $created = $obj['CensusVerification']['created'];
                $status = $obj['CensusVerification']['status'];
                $year = $obj['SchoolYear']['name'];
                $by = trim($obj['SecurityUser']['first_name'] . ' ' . $obj['SecurityUser']['last_name']);
                $date = $this->Utility->formatDate($created, null, false) . ' ' . date('H:i:s', strtotime($created));
                $status = '<span class="' . ($status == 1 ? 'green' : 'red') . '">' . ($status == 1 ? __('Verified') : __('Unverified')) . '</span>';

                if ($i == count($data) - 1 || ($counter !== $data[$i + 1]['SchoolYear']['name'])) {
                    if ($i != count($data) - 1) {
                        $counter = $data[$i + 1]['SchoolYear']['name'];
                    }
                    $highlight = 'selected';
                }
                ?>
                <tr class="<?php echo $highlight ?>">
                    <td><?php echo $year; ?></td>
                    <td><?php echo $by; ?></td>
                    <td><?php echo $date; ?></td>
                    <td><?php echo $status; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php $this->end(); ?>
