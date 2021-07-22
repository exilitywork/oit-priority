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

define('PLUGIN_OITPRIORITY_MIN_GLPI_VERSION', '9.4');
define('PLUGIN_OITPRIORITY_NAMESPACE', 'oitpriority');

/*if (!defined("PLUGINFIELDSUPGRADE_DIR")) {
    define("PLUGINFIELDSUPGRADE_DIR", GLPI_ROOT . "/plugins/fieldsupgrade");
 }
 
 if (!defined("PLUGINFIELDSUPGRADE_DOC_DIR")) {
    define("PLUGINFIELDSUPGRADE_DOC_DIR", GLPI_PLUGIN_DOC_DIR . "/fieldsupgrade");
 }
 if (!file_exists(PLUGINFIELDSUPGRADE_DOC_DIR)) {
    mkdir(PLUGINFIELDSUPGRADE_DOC_DIR);
 }

if (!defined("PLUGINFIELDSUPGRADE_CLASS_PATH")) {
    define("PLUGINFIELDSUPGRADE_CLASS_PATH", PLUGINFIELDSUPGRADE_DOC_DIR . "/inc");
 }
 if (!file_exists(PLUGINFIELDSUPGRADE_CLASS_PATH)) {
    mkdir(PLUGINFIELDSUPGRADE_CLASS_PATH);
 }
 
 if (!defined("PLUGINFIELDSUPGRADE_FRONT_PATH")) {
    define("PLUGINFIELDSUPGRADE_FRONT_PATH", PLUGINFIELDSUPGRADE_DOC_DIR."/front");
 }
 if (!file_exists(PLUGINFIELDSUPGRADE_FRONT_PATH)) {
    mkdir(PLUGINFIELDSUPGRADE_FRONT_PATH);
 }*/

/**
 * Plugin description
 *
 * @return boolean
 */
function plugin_version_oitpriority() {
    return [
      'name' => 'OIT Priority for Belwest',
      'version' => '0.1',
      'author' => 'BELWEST - Kapeshko Oleg',
      'homepage' => '',
      'license' => 'GPLv2+',
      'minGlpiVersion' => PLUGIN_OITPRIORITY_MIN_GLPI_VERSION,
    ];
}

/**
 * Initialize plugin
 *
 * @return boolean
 */
function plugin_init_oitpriority() {
    if (Session::getLoginUserID()) {
        global $PLUGIN_HOOKS;
        $PLUGIN_HOOKS['csrf_compliant'][PLUGIN_OITPRIORITY_NAMESPACE] = true;
        $PLUGIN_HOOKS['add_javascript'][PLUGIN_OITPRIORITY_NAMESPACE][]='js/calculation.js';
        $PLUGIN_HOOKS['post_item_form'][PLUGIN_OITPRIORITY_NAMESPACE] = ['PluginOitpriorityCalculation', 'post_item_form'];
        $PLUGIN_HOOKS['item_add'][PLUGIN_OITPRIORITY_NAMESPACE] = array('Ticket' => array('PluginOitpriorityCalculation', 'item_add_priority_time'));
        $PLUGIN_HOOKS['pre_item_update'][PLUGIN_OITPRIORITY_NAMESPACE] = array('Ticket' => array('PluginOitpriorityCalculation', 'item_update_priority_time'));
    }
}

/**
 * Check if plugin prerequisites are met
 *
 * @return boolean
 */
function plugin_oitpriority_check_prerequisites() {
    $prerequisites_check_ok = false;

    try {
        if (version_compare(GLPI_VERSION, PLUGIN_OITPRIORITY_MIN_GLPI_VERSION, '<')) {
            throw new Exception('This plugin requires GLPI >= ' . PLUGIN_OITPRIORITY_MIN_GLPI_VERSION);
        }

        $prerequisites_check_ok = true;
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    return $prerequisites_check_ok;
}

/**
 * Check if config is compatible with plugin
 *
 * @return boolean
 */
function plugin_oitpriority_check_config() {
    // nothing to do
    return true;
}
