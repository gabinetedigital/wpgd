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


/* -- Functions that process the requests of the above views -- */


function _process_add() {
    $ctx = array();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    }
    return $ctx;
}


/* -- Installation functions - They care about the database setup of our
      sub-plugin -- */


global $wpgd_admin_videos_schema_version;
$wpgd_admin_videos_schema_version = '0.1';


function wpgd_admin_videos_install() {
    global $wpdb;
    global $wpgd_admin_videos_schema_version;

    $now = "'0000-00-00 00:00:00'";

    /* definition of tables that holds videos and sources */
    $videos = $wpdb->prefix . "wpgd_admin_videos";
    $sources = $wpdb->prefix . "wpgd_admin_videos_sources";
    $sql = "CREATE TABLE " . $videos . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        date datetime DEFAULT " . $now . " NOT NULL,
        author VARCHAR(200) NOT NULL,
        license tinytext NOT NULL,
        description text NOT NULL,
        video_width integer NOT NULL,
        video_height integer NOT NULL,
        UNIQUE KEY id (id)
    );

    CREATE TABLE " . $sources . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        video_id mediumint(9) NOT NULL,
        format VARCHAR(128) NOT NULL,
        url VARCHAR(256) NOT NULL,
        UNIQUE KEY id (id)
    );

    ALTER TABLE " . $sources . "
      ADD CONSTRAINT FK_video
      FOREIGN KEY (video_id) REFERENCES " . $videos . "(id)
      ON UPDATE CASCADE
      ON DELETE CASCADE;
    ";

    require_once(ABSPATH . "wp-admin/includes/upgrade.php");
    dbDelta($sql);

    add_option(
        "wpgd_admin_videos_schema_version",
        $wpgd_admin_videos_schema_version);
}

?>
