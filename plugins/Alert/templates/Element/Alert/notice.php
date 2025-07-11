<?php $message = html_entity_decode($entity->message); ?>
<?= $this->Html->div('message', $message, ['escape' => false]) ?>

