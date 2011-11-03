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

include_once('wpgd.templating.php');

$renderer = new WpGdTemplatingRenderer();

add_action('admin_menu', 'wpgd_videos_menu');

function wpgd_videos_menu() {
    $menupage = __FILE__;

    add_menu_page('Videos', 'Videos', 'administrator', $menupage,
                  'wpgd_videos_submenu_allvideos');

    add_submenu_page(
        $menupage, 'Add New', 'Add New',
        'manage_options', 'gd-videos-add', 'wpgd_videos_submenu_add');

    add_submenu_page(
        $menupage, 'Videos in home', 'Videos in home',
        'manage_options', 'gd-videos-home', 'wpgd_videos_submenu_home');

}


function wpgd_videos_submenu_allvideos() {
    global $renderer;
    echo $renderer->render('admin/videos/listing.html');
}


function wpgd_videos_submenu_add() {
    global $renderer;
    echo $renderer->render('admin/videos/add.html');
}


function wpgd_videos_submenu_home() {
    global $renderer;
    echo $renderer->render('admin/videos/home.html');
}

?>
