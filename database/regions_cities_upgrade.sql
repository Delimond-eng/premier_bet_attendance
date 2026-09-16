-- Mise a niveau Regions / Cites / Stations
-- Base cible: MySQL 8+
-- Les stations sans region_id ou city_id utilisent Africa/Kinshasa dans le pointage.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `regions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `capital` VARCHAR(255) NOT NULL,
    `cities` JSON NULL,
    `timezone` VARCHAR(64) NOT NULL DEFAULT 'Africa/Kinshasa',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `regions_name_unique` (`name`),
    UNIQUE KEY `regions_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `regions` (`name`, `code`, `capital`, `cities`, `timezone`, `created_at`, `updated_at`) VALUES
('Kinshasa', 'CD-KN', 'Kinshasa', JSON_ARRAY('Kinshasa'), 'Africa/Kinshasa', NOW(), NOW()),
('Kongo-Central', 'CD-BC', 'Matadi', JSON_ARRAY('Matadi', 'Boma', 'Mbanza-Ngungu'), 'Africa/Kinshasa', NOW(), NOW()),
('Kwango', 'CD-KG', 'Kenge', JSON_ARRAY('Kenge', 'Popokabaka'), 'Africa/Kinshasa', NOW(), NOW()),
('Kwilu', 'CD-KW', 'Bandundu', JSON_ARRAY('Bandundu', 'Kikwit', 'Idiofa'), 'Africa/Kinshasa', NOW(), NOW()),
('Mai-Ndombe', 'CD-MN', 'Inongo', JSON_ARRAY('Inongo', 'Kutu'), 'Africa/Kinshasa', NOW(), NOW()),
('Equateur', 'CD-EQ', 'Mbandaka', JSON_ARRAY('Mbandaka', 'Bikoro'), 'Africa/Kinshasa', NOW(), NOW()),
('Mongala', 'CD-MO', 'Lisala', JSON_ARRAY('Lisala', 'Bumba'), 'Africa/Kinshasa', NOW(), NOW()),
('Nord-Ubangi', 'CD-NU', 'Gbadolite', JSON_ARRAY('Gbadolite', 'Bosobolo'), 'Africa/Kinshasa', NOW(), NOW()),
('Sud-Ubangi', 'CD-SU', 'Gemena', JSON_ARRAY('Gemena', 'Libenge', 'Zongo'), 'Africa/Kinshasa', NOW(), NOW()),
('Tshuapa', 'CD-TU', 'Boende', JSON_ARRAY('Boende', 'Befale'), 'Africa/Kinshasa', NOW(), NOW()),
('Ituri', 'CD-IT', 'Bunia', JSON_ARRAY('Bunia', 'Mahagi', 'Aru'), 'Africa/Lubumbashi', NOW(), NOW()),
('Nord-Kivu', 'CD-NK', 'Goma', JSON_ARRAY('Goma', 'Beni', 'Butembo', 'Rutshuru'), 'Africa/Lubumbashi', NOW(), NOW()),
('Sud-Kivu', 'CD-SK', 'Bukavu', JSON_ARRAY('Bukavu', 'Uvira', 'Baraka'), 'Africa/Lubumbashi', NOW(), NOW()),
('Maniema', 'CD-MA', 'Kindu', JSON_ARRAY('Kindu', 'Kasongo'), 'Africa/Lubumbashi', NOW(), NOW()),
('Haut-Lomami', 'CD-HL', 'Kamina', JSON_ARRAY('Kamina', 'Bukama'), 'Africa/Lubumbashi', NOW(), NOW()),
('Lualaba', 'CD-LB', 'Kolwezi', JSON_ARRAY('Kolwezi', 'Dilolo'), 'Africa/Lubumbashi', NOW(), NOW()),
('Haut-Katanga', 'CD-HK', 'Lubumbashi', JSON_ARRAY('Lubumbashi', 'Likasi', 'Kipushi'), 'Africa/Lubumbashi', NOW(), NOW()),
('Tanganyika', 'CD-TA', 'Kalemie', JSON_ARRAY('Kalemie', 'Moba', 'Nyunzu'), 'Africa/Lubumbashi', NOW(), NOW()),
('Haut-Uele', 'CD-HU', 'Isiro', JSON_ARRAY('Isiro', 'Watsa', 'Dungu'), 'Africa/Kinshasa', NOW(), NOW()),
('Bas-Uele', 'CD-BU', 'Buta', JSON_ARRAY('Buta', 'Aketi'), 'Africa/Kinshasa', NOW(), NOW()),
('Tshopo', 'CD-TO', 'Kisangani', JSON_ARRAY('Kisangani', 'Isangi'), 'Africa/Kinshasa', NOW(), NOW()),
('Lomami', 'CD-LO', 'Kabinda', JSON_ARRAY('Kabinda', 'Lusambo', 'Mwene-Ditu'), 'Africa/Kinshasa', NOW(), NOW()),
('Sankuru', 'CD-SA', 'Lusambo', JSON_ARRAY('Lusambo', 'Tshumbe'), 'Africa/Kinshasa', NOW(), NOW()),
('Kasai', 'CD-KS', 'Tshikapa', JSON_ARRAY('Tshikapa', 'Luebo'), 'Africa/Kinshasa', NOW(), NOW()),
('Kasai-Central', 'CD-KC', 'Kananga', JSON_ARRAY('Kananga', 'Demba'), 'Africa/Kinshasa', NOW(), NOW()),
('Kasai-Oriental', 'CD-KE', 'Mbuji-Mayi', JSON_ARRAY('Mbuji-Mayi', 'Mwene-Ditu', 'Kabinda'), 'Africa/Kinshasa', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `capital` = VALUES(`capital`),
    `cities` = VALUES(`cities`),
    `timezone` = VALUES(`timezone`),
    `updated_at` = NOW();

CREATE TABLE IF NOT EXISTS `cities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `region_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `timezone` VARCHAR(64) NOT NULL DEFAULT 'Africa/Kinshasa',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cities_region_id_name_unique` (`region_id`, `name`),
    KEY `cities_name_index` (`name`),
    CONSTRAINT `cities_region_id_foreign`
        FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cities` (`region_id`, `name`, `timezone`, `created_at`, `updated_at`)
SELECT r.`id`, jt.`name`, r.`timezone`, NOW(), NOW()
FROM `regions` r
CROSS JOIN JSON_TABLE(
    r.`cities`, '$[*]' COLUMNS (`name` VARCHAR(255) PATH '$')
) AS jt
WHERE NOT EXISTS (
    SELECT 1
    FROM `cities` existing
    WHERE existing.`region_id` = r.`id`
        AND existing.`name` COLLATE utf8mb4_unicode_ci
            = CONVERT(jt.`name` USING utf8mb4) COLLATE utf8mb4_unicode_ci
);

UPDATE `cities` c
INNER JOIN `regions` r ON r.`id` = c.`region_id`
SET c.`timezone` = r.`timezone`,
    c.`updated_at` = NOW();

-- Les colonnes restent NULL pour conserver les stations existantes et activer
-- le fallback Africa/Kinshasa lorsque aucune region/cite n'est définie.
SET @has_region_id := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites' AND COLUMN_NAME = 'region_id'
);
SET @sql := IF(@has_region_id = 0,
    'ALTER TABLE `sites` ADD COLUMN `region_id` BIGINT UNSIGNED NULL AFTER `code`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_city_id := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites' AND COLUMN_NAME = 'city_id'
);
SET @sql := IF(@has_city_id = 0,
    'ALTER TABLE `sites` ADD COLUMN `city_id` BIGINT UNSIGNED NULL AFTER `region_id`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Repare les stations qui possedent deja une cite mais pas encore de region.
UPDATE `sites` s
INNER JOIN `cities` c ON c.`id` = s.`city_id`
SET s.`region_id` = c.`region_id`
WHERE s.`city_id` IS NOT NULL AND s.`region_id` IS NULL;

SET @has_region_fk := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites'
      AND COLUMN_NAME = 'region_id' AND REFERENCED_TABLE_NAME = 'regions'
);
SET @sql := IF(@has_region_fk = 0,
    'ALTER TABLE `sites` ADD CONSTRAINT `sites_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_city_fk := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites'
      AND COLUMN_NAME = 'city_id' AND REFERENCED_TABLE_NAME = 'cities'
);
SET @sql := IF(@has_city_fk = 0,
    'ALTER TABLE `sites` ADD CONSTRAINT `sites_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_region_city_index := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites'
      AND INDEX_NAME = 'sites_region_id_city_id_index'
);
SET @sql := IF(@has_region_city_index = 0,
    'ALTER TABLE `sites` ADD INDEX `sites_region_id_city_id_index` (`region_id`, `city_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- Controle final:
SELECT r.`name` AS region, r.`code`, r.`timezone`, COUNT(c.`id`) AS cities_count
FROM `regions` r
LEFT JOIN `cities` c ON c.`region_id` = r.`id`
GROUP BY r.`id`, r.`name`, r.`code`, r.`timezone`
ORDER BY r.`name`;

SELECT COUNT(*) AS stations_without_region_or_city
FROM `sites`
WHERE `region_id` IS NULL OR `city_id` IS NULL;
