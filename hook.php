<?php
/**
 * -------------------------------------------------------------------------
 * Oitpriority plugin for GLPI
 * Copyright (C) 2021 by the Belwest, Kapeshko Oleg.
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Oitpriority.
 *
 * Oitpriority is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Oitpriority is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Oitpriority. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

/**
 * Install the plugin
 *
 * @return boolean
 */
function plugin_oitpriority_install() {
    global $DB;

    if (!$DB->tableExists('glpi_plugin_')) {
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_oitpriority_calculations` 
            (
                `id` INT(11) NOT NULL AUTO_INCREMENT, 
                `assign_date` DATETIME DEFAULT NULL, 
                `expire_date` DATETIME DEFAULT NULL, 
                `status` TINYINT DEFAULT 2,
                PRIMARY KEY (`id`)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;
        ";
        $DB->query($create_table_query) or die($DB->error());
    }

    return true;
}

/**
 * Uninstall the plugin
 *
 * @return boolean
 */
function plugin_oitpriority_uninstall() {
    global $DB;

    //$drop_table_query = "DROP TABLE IF EXISTS `glpi_plugin_unreadmessages`";

    //return $DB->query($drop_table_query) or die($DB->error());
    return true;
}

function plugin_oitpriority_getAddSearchOptions($itemtype) {
    if (isset($_SESSION['glpiactiveentities'])
            && is_array($_SESSION['glpiactiveentities'])
            && count($_SESSION['glpiactiveentities']) > 0) {
 
        if (in_array($itemtype, ['Ticket'])) {
            global $DB;

            $opt = [];

            $opt[90500]['name']           = 'По сроку выполнения (для ОИТ)';
            $opt[90500]['table']          = 'glpi_plugin_oitpriority_calculations';
            $opt[90500]['field']          = 'status';
            $opt[90500]['linkfield']      = 'id';
            $opt[90500]['datatype']       = 'specific';
            $opt[90500]['searchtype']      = ['equals', 'notequals'];
            $opt[90500]['searchequalsonfield'] = true;

            return $opt;
        }
    }
 
    return null;
}
