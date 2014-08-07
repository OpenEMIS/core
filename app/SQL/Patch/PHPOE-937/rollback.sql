UPDATE `field_options` SET `order` = `order` - 1 WHERE `order` > 12;

DELETE FROM `field_options` WHERE `code` = 'CensusGrid';


