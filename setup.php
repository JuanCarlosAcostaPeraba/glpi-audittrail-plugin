<?php

if (!defined('GLPI_ROOT')) {
   die('Direct access not allowed');
}

define('PLUGIN_AUDITTRAIL_VERSION', '1.0.0');

/**
 * Init the plugin of the plugin
 *
 * @return void
 */
function plugin_init_audittrail()
{
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['audittrail'] = true;

   if (Plugin::isPluginActive('audittrail')) {
      // Register types
      Plugin::registerClass('PluginAudittrailLog', ['addtabon' => ['Ticket', 'TicketTask']]);

      // Hook for item update/add/delete
      $PLUGIN_HOOKS['item_add']['audittrail'] = [
         'Ticket' => 'PluginAudittrailLog::logAdd',
         'TicketTask' => 'PluginAudittrailLog::logAdd'
      ];
      $PLUGIN_HOOKS['item_update']['audittrail'] = [
         'Ticket' => 'PluginAudittrailLog::logUpdate',
         'TicketTask' => 'PluginAudittrailLog::logUpdate'
      ];
      $PLUGIN_HOOKS['item_purge']['audittrail'] = [
         'Ticket' => 'PluginAudittrailLog::logDelete',
         'TicketTask' => 'PluginAudittrailLog::logDelete'
      ];

      // Hook for pre_item_update (to capture old values)
      $PLUGIN_HOOKS['pre_item_update']['audittrail'] = [
         'Ticket' => 'PluginAudittrailLog::preUpdate',
         'TicketTask' => 'PluginAudittrailLog::preUpdate'
      ];
   }
}

/**
 * Get the name and the version of the plugin
 *
 * @return array
 */
function plugin_version_audittrail()
{
   return [
      'name' => "Audit Trail",
      'version' => PLUGIN_AUDITTRAIL_VERSION,
      'author' => "Juan Carlos Acosta Perabá",
      'license' => "GPLv2+",
      'homepage' => 'https://github.com/JuanCarlosAcostaPeraba/glpi-audittrail-plugin',
      'requirements' => [
         'glpi' => [
            'min' => '11.0.0',
            'max' => '11.0.99'
         ]
      ]
   ];
}

/**
 * Check prerequisites for the plugin
 *
 * @return boolean
 */
function plugin_audittrail_check_prerequisites()
{
   return true;
}

/**
 * Check configuration for the plugin
 *
 * @param boolean $verbose
 * @return boolean
 */
function plugin_audittrail_check_config($verbose = false)
{
   if (true) { // Standard check
      return true;
   }

   if ($verbose) {
      echo "Installed / not configured";
   }
   return false;
}
