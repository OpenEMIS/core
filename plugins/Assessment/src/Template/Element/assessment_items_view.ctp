<!-- POCOR-7999 for readibility -->
<div class="table-in-view">
    <table class="table">
        <thead>
        <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
        <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
        <th><?= $this->Label->get('Assessments.classification'); ?></th>
        </thead>
        <?php if (isset($data['assessment_items'])) : ?>
            <tbody>
            <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                <tr>
                    <td><?= $item->education_subject_name; ?></td>
                    <td><?= $item->weight; ?></td>
                    <td><?= $item->classification; ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        <?php endif ?>
    </table>
</div>