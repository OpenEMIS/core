<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8873 extends AbstractMigration
{
  public function up(): void
  {
    $this->execute('CREATE TABLE `z_8873_field_options` LIKE `field_options`');
    $this->execute('INSERT INTO `z_8873_field_options` SELECT * FROM `field_options`');
    $this->execute("INSERT INTO `field_options` (`name`, `category`, `table_name`, `order`, `modified_by`, `modified`, `created_by`, `created`) VALUES
        ('Item Types', 'Others', 'item_types', 144, NULL, NULL, 1, NOW()),
        ('Stock Units', 'Others', 'stock_units', 145, NULL, NULL, 1, NOW())");

    $this->execute("CREATE TABLE `item_types` (
            `id` int AUTO_INCREMENT PRIMARY KEY NOT NULL,
            `name` varchar(50) NOT NULL,
            `order` int NOT NULL,
            `visible` int NOT NULL DEFAULT '1',
            `editable` int NOT NULL DEFAULT '1',
            `default` int NOT NULL DEFAULT '0',
            `international_code` varchar(50) DEFAULT NULL,
            `national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int NOT NULL,
            `created` datetime NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='This table contains the list of items chargable to individual students'");

    $this->execute("CREATE TABLE `stock_units` (
            `id` int AUTO_INCREMENT PRIMARY KEY NOT NULL,
            `name` varchar(50) NOT NULL,
            `order` int NOT NULL,
            `visible` int NOT NULL DEFAULT '1',
            `editable` int NOT NULL DEFAULT '1',
            `default` int NOT NULL DEFAULT '0',
            `international_code` varchar(50) DEFAULT NULL,
            `national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int NOT NULL,
            `created` datetime NOT NULL
          )");

    $this->execute("CREATE TABLE `institution_consumables` (
      `id` int AUTO_INCREMENT PRIMARY KEY NOT NULL,
      `item_type_id` int NOT NULL,
      `bin_no` varchar(255),
      `stock_unit_id` int NOT NULL,
      `institution_id` int NOT NULL,
      `minimum` int,
      `modified_user_id` int DEFAULT NULL,
      `modified` datetime DEFAULT NULL,
      `created_user_id` int NOT NULL,
      `created` datetime NOT NULL,
      CONSTRAINT `fk_item_type_id` FOREIGN KEY (`item_type_id`) REFERENCES `item_types`(`id`),
      CONSTRAINT `fk_stock_unit_id` FOREIGN KEY (`stock_unit_id`) REFERENCES `stock_units`(`id`),
      CONSTRAINT `fk_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`),
      CONSTRAINT `fk_modified_user_id` FOREIGN KEY (`modified_user_id`) REFERENCES `security_users`(`id`),
      CONSTRAINT `fk_created_user_id`  FOREIGN KEY (`created_user_id`) REFERENCES `security_users`(`id`)
  );
  ");

        $this->execute("CREATE TABLE `institution_consumable_transactions` (
      `id` int AUTO_INCREMENT PRIMARY KEY NOT NULL,
      `institution_consumable_id` int NOT NULL,
      `date` date NOT NULL,
      `received` int NOT NULL,
      `issued` int NOT NULL,
      `balance` int NOT NULL,
      `init` int NOT NULL,
      `modified_user_id` int DEFAULT NULL,
      `modified` datetime DEFAULT NULL,
      `created_user_id` int NOT NULL,
      `created` datetime NOT NULL,
      CONSTRAINT `fk_institution_consumable_id` FOREIGN KEY (`institution_consumable_id`) REFERENCES `institution_consumables`(`id`)
    );");

    // $this->execute("CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(50) NOT NULL,order int NOT NULL,visible int NOT NULL DEFAULT 1,editable int NOT NULL DEFAULT 1,default int NOT NULL DEFAULT 0,international_code varchar(50),national_code varchar(50),modified_user_id int,modified datetime,created_user_id int NOT NULL,created datetime DEFAULT CURRENT_TIMESTAMP);");
  }

  public function down()
  {
    $this->execute('DROP TABLE IF EXISTS `field_options`');
    $this->execute('RENAME TABLE `z_8873_field_options` TO `field_options`');
    $this->execute('DROP TABLE stock_units');
    $this->execute('DROP TABLE item_types');
  }
}
