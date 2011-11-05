<?php /* -*- Mode: php; c-basic-offset:4; -*- */
/* Copyright (C) 2011  Governo do Estado do Rio Grande do Sul
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * A shortcut to log the user in before making any `wp_' call and escape
 * all the received arguments of an exposed method.
 *
 * Borrowed from `exapi'
 *
 * @params array $args Arguments that will be escaped with a wordpress
 *  xmlrpc utility
 */
function _wpgd_method_header(&$args) {
    // We don't like smart-ass people
    global $wp_xmlrpc_server;
    $wp_xmlrpc_server->escape( $args );

    // Reading the attribute list
    $username = array_shift($args);
    $password = array_shift($args);

    // All methods in this API are being protected
    if (!$user = $wp_xmlrpc_server->login($username, $password))
        return $wp_xmlrpc_server->error;
    return $args;
}


function wpgd_getHighlightedVideos($args) {
    if (!is_array($args = _wpgd_method_header($args))) {
        return $args;
    }
    $limit = null;
    if (isset($args[1]))
        $limit = $args[1];
    return wpgd_videos_get_highlighted_videos($limit);
}


function wpgd_register_methods($methods) {
    $methods['wpgd.getHighlightedVideos'] = 'wpgd_getHighlightedVideos';
    return $methods;
}

add_filter('xmlrpc_methods', 'wpgd_register_methods');

?>
