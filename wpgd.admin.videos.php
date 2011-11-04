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
include_once('inc.validation.php');

$renderer = new WpGdTemplatingRenderer();


/* -- Registering some javascripts and styles used by our interface --  */


add_action('init', function () {
    if (is_admin()) {
        /* javascripts */
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script(
            'jquery-ui-datepicker',
            plugins_url('static/js/jquery-ui-datepicker.js', __FILE__));
        wp_enqueue_script(
            'wpgd-videos',
            plugins_url('static/js/videos.js', __FILE__));

        /* stylesheets */
        wp_enqueue_style(
            'jquery-ui-datepicker',
            plugins_url('static/css/jquery-ui-datepicker.css', __FILE__));
    }
});


/* -- Adding the admin pages we need to this plugin-- */


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


/* -- Views that renders the templates being used in this app -- */


function wpgd_videos_submenu_allvideos() {
    global $renderer;
    echo $renderer->render('admin/videos/listing.html', _process_listing());
}


function wpgd_videos_submenu_add() {
    global $renderer;
    echo $renderer->render('admin/videos/add.html', _process_add());
}


function wpgd_videos_submenu_home() {
    global $renderer;
    echo $renderer->render('admin/videos/home.html');
}


/* -- Functions that process the requests of the above views -- */


$video_fields = array(
    'title', 'date', 'author', 'license', 'description',
    'video_width', 'video_height'
);


$source_fields = array(
    'format', 'url'
);


function _process_listing() {
    global $wpdb;
    $ctx = array();

    $videos = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "SELECT id, title, date, author, description FROM $videos";
    $ctx['listing'] = $wpdb->get_results($wpdb->prepare($sql));
    return $ctx;
}


function _process_add() {
    global $video_fields;
    global $source_fields;

    $ctx = array();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        global $wpdb;
        $videos_table = $wpdb->prefix . "wpgd_admin_videos";
        $sources_table = $wpdb->prefix . "wpgd_admin_videos_sources";

        /* Validating the source received from the form */
        $sources = array();
        $incomplete_sources = array();

        for ($i = 0; $i < sizeof($_POST['formats']); $i++) {
            /* Form that will be validated */
            $source = array(
                'format' => trim($_POST['formats'][$i]),
                'url' => trim($_POST['urls'][$i])
            );

            if ($source['format'] !== '' && $source['url'] !== '') {
                /* Both values are ok, let's rock! */
                $sources[] = $source;
            } else if ($source['format'] === '' && $source['url'] === '') {
                /* There's nothing to be done here, the user didn't
                   fill any of the requested fields */
                continue;
            } else {
                /* The user forgot to fill one of the fields, let's give
                   him/her another chance to do it */
                $incomplete_sources[] = $source;
            }
        }

        /* Validating the rest of the form */
        try {
            $fields = _validate_array($video_fields);

            /* Maybe it's everything ok with the normal fields, but
               let's find out what happened in the source list
               validation */
            if (sizeof($sources) === 0) {
                throw new ValidationException(array());
            }
        } catch (ValidationException $exc) {
            return array(
                'errors' => $exc->getErrors(),
                'fields' => $_POST,
                'source_fields' => array_merge($sources, $incomplete_sources),
                'source_incomplete' => $incomplete_sources
            );
        }

        /* Finally, inserting the video */
        $wpdb->insert(
            $videos_table,
            array(
                'title' => $fields['title'],
                'author' => $fields['author'],
                'license' => $fields['license'],
                'description' => $fields['description'],
                'video_width' => $fields['video_width'],
                'video_height' => $fields['video_height']
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d')
        );

        /* This info will be needed when adding sources */
        $video_id = $wpdb->insert_id;

        /* Saving video `sources' */
        foreach ($sources as $s) {
            /* Form that will be validated */
            $wpdb->insert(
                $sources_table,
                array(
                    'video_id' => $video_id,
                    'format' => $s['format'],
                    'url' => $s['url']
                ),
                array('%d', '%s', '%s'));
        }
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
