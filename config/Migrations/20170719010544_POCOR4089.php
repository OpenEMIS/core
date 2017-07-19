<?php

use Phinx\Migration\AbstractMigration;

class POCOR4089 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4089_education_grades` LIKE `education_grades`');
        $this->execute('INSERT INTO `z_4089_education_grades` SELECT * FROM `education_grades`');

        $sql = <<<EOT
SET @index := 1;
SET @prev := '';

UPDATE `education_grades` edu_grades
INNER JOIN (
    SELECT
        g.`id`,
        @current := g.`code`,
        (@index := IF(@current = @prev, @index + 1, 1)) AS current_index,
        @prev := g.`code`
    FROM `education_grades` g
    INNER JOIN (
        SELECT `code`,
        COUNT(*) AS `count`
        FROM `education_grades`
        GROUP BY `code`
        HAVING `count` > 1
    ) c
    ON g.`code` = c.`code`
    ORDER BY g.`code`, g.`id`
) ind
ON ind.`id` = edu_grades.`id`
SET `code` = CONCAT(edu_grades.`code`, CONCAT('-', ind.`current_index`))
EOT;

        $this->execute($sql);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `education_grades`');
        $this->execute('RENAME TABLE `z_4089_education_grades` TO `education_grades`');
    }
}
