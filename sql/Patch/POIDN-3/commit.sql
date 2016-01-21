-- db_patches
INSERT INTO `db_patches` VALUES ('POIDN-3', NOW());

-- authentication_type_attributes
CREATE TABLE `authentication_type_attributes` (
  `id` CHAR(36) NOT NULL COMMENT '',
  `authentication_type` VARCHAR(50) NOT NULL COMMENT '',
  `authentication_field` VARCHAR(50) NOT NULL COMMENT '',
  `attribute_name` VARCHAR(50) NOT NULL COMMENT '',
  `value` VARCHAR(100) NULL COMMENT '',
  PRIMARY KEY (`id`)  COMMENT '');