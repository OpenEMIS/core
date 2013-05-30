<?php
Configure::write('Process.Olap.xml', array(
    'filename' => 'processes.xml',
    'path' => APP.'Lib'.DS.'Olap'
//    'path' => APP.'Plugin'.DS.'DataProcessing'.DS.'olap'
));
Configure::write('Process.Olap.log', array(
//    'filename' => 'processes.log',
    'path' => APP.'Lib'.DS.'logs'.DS.'olap'
//    'path' => APP.'Plugin'.DS.'DataProcessing'.DS.'olap'
));
Configure::write('Process.Olap.error', array(
    'filename' => 'processes.log',
    'path' => APP.'tmp'.DS.'logs'
//    'path' => APP.'Plugin'.DS.'DataProcessing'.DS.'olap'
));