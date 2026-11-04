<?php $message = html_entity_decode($entity->message); ?>
<?php //POCOR-9863: reset.css sets "ol, ul { list-style: none; }" and zero padding globally,
      // so restore list markers for the rich-text (CKEditor) notice message ?>
<style>
    .notice-content ul,
    .notice-content ol { padding-inline-start: 20px; margin: 0 0 10px 0; }
    .notice-content ul { list-style: disc outside; }
    .notice-content ol { list-style: decimal outside; }
    .notice-content ul ul { list-style-type: circle; }
    .notice-content ul ul ul { list-style-type: square; }
    .notice-content li { display: list-item; }
</style>
<?= $this->Html->div('message notice-content', $message, ['escape' => false]) ?>
