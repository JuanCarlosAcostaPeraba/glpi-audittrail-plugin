<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

/**
 * Install hook
 *
 * @return boolean
 */
function plugin_audittrail_install()
{
    global $DB;

    $migration = new Migration(PLUGIN_AUDITTRAIL_VERSION);

    if (!$DB->tableExists('glpi_plugin_audittrail_logs')) {
        $query = "CREATE TABLE `glpi_plugin_audittrail_logs` (
         `id` INT(11) NOT NULL AUTO_INCREMENT,
         `itemtype` VARCHAR(100) NOT NULL,
         `items_id` INT(11) NOT NULL,
         `users_id` INT(11) DEFAULT NULL,
         `date_mod` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         `action` VARCHAR(50) NOT NULL,
         `field` VARCHAR(100) DEFAULT NULL,
         `old_value` TEXT DEFAULT NULL,
         `new_value` TEXT DEFAULT NULL,
         PRIMARY KEY (`id`),
         INDEX `item` (`itemtype`, `items_id`),
         INDEX `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->doQuery($query);
    }

    $migration->executeMigration();

    return true;
}

/**
 * Uninstall hook
 *
 * @return boolean
 */
function plugin_audittrail_uninstall()
{
    global $DB;

    $DB->dropTable('glpi_plugin_audittrail_logs');

    return true;
}
