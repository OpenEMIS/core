<?php if (!empty($attr['data'])) { ?>
<div class="input clearfix">
    <?php if ($action != 'view') { ?>
        <label><?= $this->Label->get('InstitutionSubjects.past_teachers'); ?></label>
    <?php } ?>
    <div class="form-input table-full-width">
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= $this->Label->get('InstitutionSubjects.teacher_name'); ?></th>
                            <th><?= $this->Label->get('InstitutionSubjects.start_date'); ?></th>
                            <th><?= $this->Label->get('InstitutionSubjects.end_date'); ?></th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php 
                        //pr($attr['data']);
                        foreach($attr['data'] as $index) { ?>
                        <tr>
                            <td class="vertical-align-top"><?php echo $index['name']; ?></td>
                            <td class="vertical-align-top"><?php echo $index['start_date']; ?></td>
                            <td class="vertical-align-top"><?php echo $index['end_date']; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>                
                </table>
            </div>
        </div>
    </div>
</div>
<?php 
    } else { 
        if ($action == 'view') {
            echo "-";
        }
    } 
?>