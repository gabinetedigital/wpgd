<?php /* -*- Mode: php; c-basic-offset:4; -*- */
/* Copyright (C) 2011  Lincoln de Sousa <lincoln@comum.org>
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

/*
Plugin Name: WpGD
Plugin URI: http://trac.gabinetedigital.rs.gov.br
Description: Interaction of the gd tools with wordpress
Version: 0.1.0
Author: Lincoln de Sousa <lincoln@gg.rs.gov.br>
Author URI: http://gabinetedigital.rs.gov.br
License: AGPL3
*/


include("wpgd.thumbs.php");
include("wpgd.admin.php");
include("wpgd.admin.govp.php");
include("wpgd.admin.videos.php");
include("wpgd.xmlrpc.php");

register_activation_hook(__FILE__, 'wpgd_admin_videos_install');

function wpgd_admin_govp_install() {
    add_role( 'wpgd_moderator', 'Moderador',
              array( 'read' => true,
                     'moderate_contrib' => true ) );

    $role_object = get_role( 'administrator' );
    $role_object->add_cap( 'moderate_contrib' );
}

function wpgd_admin_govp_uninstall() {
    remove_role( 'wpgd_moderator');
    $role_object = get_role( 'administrator' );
    $role_object->remove_cap( 'moderate_contrib' );
}

register_activation_hook(__FILE__, 'wpgd_admin_govp_install');
register_deactivation_hook(__FILE__, 'wpgd_admin_govp_uninstall');

wpgd_thumbs_init_sizes();

?>
