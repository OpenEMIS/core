ALTER TABLE `datawarehouse_indicators` 
ADD COLUMN `classification` VARCHAR(100) NULL AFTER `datawarehouse_field_id`;

Update datawarehouse_indicators set classification = 'Education'