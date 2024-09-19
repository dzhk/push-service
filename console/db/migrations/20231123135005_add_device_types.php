<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeviceTypes extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $sql = "
            CREATE TABLE `device_type` (
                `id`    int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`  varchar(10) NOT NULL,
                `title` varchar(20) NOT NULL
            )
            COLLATE = utf8mb4_bin;

            CREATE INDEX `device_type_name`
                ON `device_type` (`name`);

            INSERT INTO `device_type` VALUES 
              (4, 'mob', 'Смартфон'), 
              (2, 'pc', 'ПК'), 
              (5, 'tab', 'Планшет'), 
              (3, 'tv', 'TV'), 
              (0, 'other', 'Остальное');

            ALTER TABLE device_type AUTO_INCREMENT=5;
        ";
        $this->execute($sql);
    }

    public function down()
    {
        $this->execute("DROP TABLE `device_type`");
    }
}
