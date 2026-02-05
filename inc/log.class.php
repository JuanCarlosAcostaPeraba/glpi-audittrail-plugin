<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

class PluginAudittrailLog extends CommonDBTM
{

    static $old_items = [];

    /**
     * Pre-update hook to capture old values
     *
     * @param CommonDBTM $item
     * @return void
     */
    static function preUpdate(CommonDBTM $item)
    {
        $itemtype = $item->getType();
        $items_id = $item->getID();

        // Load current values from DB before they are overwritten
        $old_item = new $itemtype();
        if ($old_item->getFromDB($items_id)) {
            self::$old_items[$itemtype][$items_id] = $old_item->fields;
        }
    }

    /**
     * Hook for item update
     *
     * @param CommonDBTM $item
     * @return void
     */
    static function logUpdate(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item->getType();
        $items_id = $item->getID();
        $users_id = Session::getLoginUserID();

        if (!isset(self::$old_items[$itemtype][$items_id])) {
            return;
        }

        $old_fields = self::$old_items[$itemtype][$items_id];
        $new_fields = $item->fields;

        // Fields to ignore (internal GLPI fields we might not want to track or that change too often)
        $ignore_fields = ['date_mod'];

        foreach ($new_fields as $field => $new_value) {
            if (in_array($field, $ignore_fields)) {
                continue;
            }

            if (array_key_exists($field, $old_fields)) {
                $old_value = $old_fields[$field];

                // Only log if different
                if ($old_value != $new_value) {
                    $DB->insert('glpi_plugin_audittrail_logs', [
                        'itemtype' => $itemtype,
                        'items_id' => $items_id,
                        'users_id' => $users_id,
                        'date_mod' => $_SESSION['glpi_currenttime'] ?? date("Y-m-d H:i:s"),
                        'action' => 'update',
                        'field' => $field,
                        'old_value' => self::formatValue($old_value),
                        'new_value' => self::formatValue($new_value)
                    ]);
                }
            }
        }

        // Clean up
        unset(self::$old_items[$itemtype][$items_id]);
    }

    /**
     * Hook for item add
     *
     * @param CommonDBTM $item
     * @return void
     */
    static function logAdd(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item->getType();
        $items_id = $item->getID();
        $users_id = Session::getLoginUserID();

        $DB->insert('glpi_plugin_audittrail_logs', [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'users_id' => $users_id,
            'date_mod' => $_SESSION['glpi_currenttime'] ?? date("Y-m-d H:i:s"),
            'action' => 'create',
            'field' => null,
            'old_value' => null,
            'new_value' => null
        ]);
    }

    /**
     * Hook for item delete
     *
     * @param CommonDBTM $item
     * @return void
     */
    static function logDelete(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item->getType();
        $items_id = $item->getID();
        $users_id = Session::getLoginUserID();

        $DB->insert('glpi_plugin_audittrail_logs', [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'users_id' => $users_id,
            'date_mod' => $_SESSION['glpi_currenttime'] ?? date("Y-m-d H:i:s"),
            'action' => 'delete',
            'field' => null,
            'old_value' => null,
            'new_value' => null
        ]);
    }

    /**
     * Format value for display/storage
     *
     * @param mixed $value
     * @return string
     */
    static function formatValue($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return (string) $value;
    }

    /**
     * Register tabs
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Ticket') {
            return __('Audit Trail', 'audittrail');
        }
        return '';
    }

    /**
     * Show tab content
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabbuttonindex = 0, $withtemplate = 0)
    {
        self::showForTicket($item);
        return true;
    }

    /**
     * Show logs for a ticket and its tasks
     *
     * @param Ticket $ticket
     * @return void
     */
    static function showForTicket(Ticket $ticket)
    {
        global $DB;

        $items_id = $ticket->getID();

        echo "<div class='center'>";
        echo "<h2>" . __('Audit Trail', 'audittrail') . "</h2>";

        // Get task IDs first to avoid subquery issues in the iterator
        $task_ids = [];
        $task_iterator = $DB->request([
            'SELECT' => 'id',
            'FROM' => 'glpi_tickettasks',
            'WHERE' => ['tickets_id' => $items_id]
        ]);
        foreach ($task_iterator as $row) {
            $task_ids[] = (int) $row['id'];
        }

        $where = [
            'itemtype' => 'Ticket',
            'items_id' => $items_id
        ];

        // If there are tasks, include them in the query with OR
        if (!empty($task_ids)) {
            $where = [
                'OR' => [
                    $where,
                    [
                        'itemtype' => 'TicketTask',
                        'items_id' => $task_ids
                    ]
                ]
            ];
        }

        // Get logs
        $iterator = $DB->request([
            'SELECT' => '*',
            'FROM' => 'glpi_plugin_audittrail_logs',
            'WHERE' => $where,
            'ORDER' => 'date_mod DESC'
        ]);

        if (count($iterator)) {
            echo "<table class='tab_cadre_fixehov' style='width: 100%; border-collapse: collapse;'>";
            echo "<tr>";
            echo "<th style='padding: 8px; border-bottom: 2px solid #ddd;'>" . __('Date') . "</th>";
            echo "<th style='padding: 8px; border-bottom: 2px solid #ddd;'>" . __('User') . "</th>";
            echo "<th style='padding: 8px; border-bottom: 2px solid #ddd;'>" . __('Item') . "</th>";
            echo "<th style='padding: 8px; border-bottom: 2px solid #ddd;'>" . __('Action') . "</th>";
            echo "<th style='padding: 8px; border-bottom: 2px solid #ddd;'>" . __('Field') . "</th>";
            echo "<th style='padding: 8px; border-bottom: 2px solid #ddd;'>" . __('Old value') . "</th>";
            echo "<th style='padding: 8px; border-bottom: 2px solid #ddd;'>" . __('New value') . "</th>";
            echo "</tr>";

            foreach ($iterator as $data) {
                $user = new User();
                $username = $data['users_id'] ? ($user->getFromDB($data['users_id']) ? $user->getName() : $data['users_id']) : __('System');

                $item_label = $data['itemtype'];
                if ($data['itemtype'] == 'TicketTask') {
                    $item_label = __('Ticket Task', 'audittrail') . " (" . $data['items_id'] . ")";
                } else {
                    $item_label = __('Ticket') . " (" . $data['items_id'] . ")";
                }

                $badge_color = '#6c757d'; // Default
                if ($data['action'] == 'create')
                    $badge_color = '#28a745';
                if ($data['action'] == 'update')
                    $badge_color = '#ffc107';
                if ($data['action'] == 'delete')
                    $badge_color = '#dc3545';

                echo "<tr class='tab_bg_1'>";
                echo "<td style='padding: 8px;'>" . Html::convDateTime($data['date_mod']) . "</td>";
                echo "<td style='padding: 8px;'>" . $username . "</td>";
                echo "<td style='padding: 8px;'>" . $item_label . "</td>";
                echo "<td style='padding: 8px;'><span style='background-color: $badge_color; color: white; padding: 2px 6px; border-radius: 4px;'>" . __($data['action']) . "</span></td>";
                echo "<td style='padding: 8px; font-weight: bold;'>" . ($data['field'] ?? '-') . "</td>";
                echo "<td style='padding: 8px; font-style: italic; color: #777;'>" . nl2br(Html::entities_deep($data['old_value'] ?? '')) . "</td>";
                echo "<td style='padding: 8px; color: #000;'>" . nl2br(Html::entities_deep($data['new_value'] ?? '')) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>" . __('No logs found', 'audittrail') . "</p>";
        }

        echo "</div>";
    }
}
