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

include('wpgd.templating.php');

$renderer = new WpGdTemplatingRenderer();

add_action('admin_menu', 'wpgd_menu');

function wpgd_menu() {
    $menupage = __FILE__;

    add_menu_page(
        'Gabinete Digital', 'Gabinete Digital', 'administrator',
        $menupage, 'wpgd_menu_page');

    add_submenu_page(
        $menupage, 'Gabinete Digital &mdash; Audience', 'Audience',
        'manage_options', 'gd-audience', 'wpgd_submenu_audience');

    add_submenu_page(
        $menupage, 'Gabinete Digital &mdash; Gallery', 'Gallery',
        'manage_options', 'gd-gallery', 'wpgd_submenu_gallery');
}


function wpgd_menu_page() {
    global $renderer;
    echo $renderer->render('admin/base.html');
}


function wpgd_submenu_audience() {
    global $renderer;
    echo $renderer->render('admin/audience/listing.html');
}


function wpgd_submenu_gallery() {
    global $renderer;
    echo $renderer->render('admin/audience/listing.html');
}


?>
