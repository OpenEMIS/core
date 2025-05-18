<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
foreach ($toolbarButtons as $key => $btn) {
    if (!isset($btn['type']) || $btn['type'] == 'button') {
        echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
    } else if ($btn['type'] == 'element') {
        echo $this->element($btn['element'], $btn['data'], $btn['options']);
    }
}
$this->end();

$this->start('panelBody');
$tableHeaders = [
    [__('Function') => ['style' => 'width: 300px']],
    [__('View') => ['class' => 'center']],
    [__('Edit') => ['class' => 'center']],
    [__('Add') => ['class' => 'center']],
    [__('Delete') => ['class' => 'center']],
    [__('Execute') => ['class' => 'center']]
];

foreach ($data as $section => $list) {
    echo '<div class="section-header">' . $section . '</div>';
    echo '<div class="table-wrapper">
        <div class="table-responsive">
        <table class="table table-curved">
            <thead>' . $this->Html->tableHeaders($tableHeaders) . '</thead>
            <tbody>
        ';

    foreach ($list as $obj) {
        echo '<tr>';
        echo '<td>' . $obj->name . '</td>';
        foreach ($operations as $op) {
            echo '<td class="center">' . $obj->Permissions[$op] . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table></div></div>';
}
// POCOR-8966 start
?>

<script type="text/javascript">
    $(document).ready(function() {
        $('textarea[name]').css({
            'height': '15pc',
            'width': '40% !important'
        });

        // Ensure jQuery is loaded
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        // Headers to add filter buttons to
        var filterHeaders = ['View', 'Edit', 'Add', 'Delete', 'Execute'];

        // Add filter buttons to specified table headers
        $('.table-curved th').each(function(index) {
            if (filterHeaders.includes($(this).text().trim())) {
                // Ensure only one button is added
                if ($(this).find('.btn').length === 0) {
                    var button = $('<button class="btn"><i class="fa fa-circle-o grey"></i></button>');
                    button.click(function() {
                        var columnIndex = index;
                        var filterClass = button.find('i').attr('class');
                        // Toggle button icon
                        if (filterClass === 'fa fa-circle-o grey') {
                            button.html('<i class="fa kd-check green"></i>');
                        } else if (filterClass === 'fa kd-check green') {
                            button.html('<i class="fa fa-minus grey"></i>');
                        } else if (filterClass === 'fa fa-minus grey') {
                            button.html('<i class="fa kd-cross red"></i>');
                        } else {
                            button.html('<i class="fa fa-circle-o grey"></i>');
                        }
                        var filterClass = button.find('i').attr('class');
                        console.log(filterClass + ' ' + columnIndex);
                        $('.table-curved tbody tr').each(function() {
                            var cell = $(this).find('td').eq(columnIndex);
                            if (filterClass === 'fa kd-cross red'
                                || filterClass === 'fa kd-check green'
                                || filterClass === 'fa fa-minus grey') {
                                if (cell.find('i').hasClass(filterClass)) {
                                    $(this).show();
                                } else {
                                    $(this).hide();
                                }
                            } else {
                                $(this).show();
                            }
                        });
                    });
                    $(this).append(button);
                }
            }
        });
    });
</script>
<?php
// POCOR-8966 end
$this->end();
?>
