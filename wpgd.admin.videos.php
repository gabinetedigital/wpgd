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
include_once('inc.videos.php');

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

    add_submenu_page(
        null, 'Edit Video', 'Edit Video',
        'manage_options', 'gd-videos-edit', 'wpgd_videos_submenu_edit');
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

function wpgd_videos_submenu_edit() {
    global $renderer;
    echo $renderer->render('admin/videos/add.html', _process_edit());
}


function wpgd_videos_submenu_home() {
    global $renderer;
    echo $renderer->render('admin/videos/home.html');
}


/* -- Functions that process the requests of the above views -- */


$video_fields = array(
    'title', 'date', 'author', 'license', 'description',
    'video_width', 'video_height', 'thumbnail'
);


$source_fields = array(
    'format', 'url'
);


function _process_listing() {
    global $wpdb;
    $ctx = array();

    $videos = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "
        SELECT
            id, title, date, author, description, thumbnail, status
        FROM $videos ORDER BY date DESC";
    $ctx['listing'] = $wpdb->get_results($wpdb->prepare($sql));
    return $ctx;
}


function _process_edit() {
    global $wpdb;
    $video_id = $_REQUEST['video_id'];
    $videos_table = $wpdb->prefix . "wpgd_admin_videos";
    $sources_table = $wpdb->prefix . "wpgd_admin_videos_sources";
    $ctx = array('edit' => true);

    /* Getting the video attributes */
    $ctx['fields'] = wpgd_videos_get_video($video_id);

    /* Formatting date as the user expects to see */
    $date = $ctx['fields']['date'];
    $ctx['fields']['date'] = date_format(date_create_from_format(
        "Y-m-d H:i:s", $date), 'd/m/Y');

    /* Listing the sources */
    $ctx['source_fields'] = wpgd_videos_get_sources($video_id);


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        /* Validating the rest of the form */
        try {
            $_validated = __validate_form();
            $fields = $_validated['fields'];
            $sources = $_validated['sources'];
        } catch (ValidationException $exc) {
            $ctx = array_merge($ctx, $exc->getErrors());
            $ctx['fields']['id'] = $video_id;
            return $ctx;
        }

        /* Date field handling */
        /* FIXME: hardcoded date format */
        $date = date_format(date_create_from_format(
            "d/m/Y", $fields['date']), 'Y-m-d H:i:s');

        $wpdb->update(
            $videos_table,
            array(
                'title' => $fields['title'],
                'date' => $date,
                'author' => $fields['author'],
                'license' => $fields['license'],
                'description' => $fields['description'],
                'thumbnail' => $fields['thumbnail'],
                'video_width' => $fields['video_width'],
                'video_height' => $fields['video_height'],
                'status' => isset($_POST['status'])
            ),
            array('id' => $video_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'),
            array('%d')
        );
        $ctx['fields'] = $fields;
        $ctx['fields']['status'] = isset($_POST['status']);


        /* Updating sources */
        $handled_sources = array();

        for ($i = 0; $i < sizeof($sources); $i++) {
            $s = $sources[$i];

            if (!empty($s['id'])) {
                /* Update existing sources */
                $wpdb->update(
                    $sources_table,
                    array('format' => $s['format'], 'url' => $s['url']),
                    array('id' => $s['id']),
                    array('%s', '%s'),
                    array('%d')
                );
                $handled_sources[] = $s['id'];
            } else {
                /* Dealing with the new ones */
                $wpdb->insert($sources_table,
                    array(
                        'video_id' => $video_id,
                        'format' => $s['format'],
                        'url' => $s['url']
                    ),
                    array('%d', '%s', '%s')
                );
                $sources[$i]['id'] = $wpdb->insert_id;
                $handled_sources[] = $sources[$i]['id'];
            }
        }
        /* Dealing with the removed sources */
        $excluded = implode(',', $handled_sources);
        $excluded_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $sources_table WHERE id not in ($excluded)
             AND video_id = $video_id"));
        if ($excluded_ids) {
            $excluded = join($excluded_ids, ',');
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $sources_table WHERE id in ($excluded)"));
        }

        $ctx['source_fields'] = $sources;
    }

    $ctx['fields']['id'] = $video_id;
    return $ctx;
}


function __validate_sources() {
    $sources = array();
    $incomplete_sources = array();

    for ($i = 0; $i < sizeof($_POST['formats']); $i++) {
        /* Form that will be validated */
        $source = array(
            'format' => trim($_POST['formats'][$i]),
            'url' => trim($_POST['urls'][$i])
        );

        /* In case of edit */
        if (isset($_POST['sids'])) {
            $source['id'] = trim($_POST['sids'][$i]);
        }

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

    return array(
        'sources' => $sources,
        'incomplete_sources' => $incomplete_sources
    );
}


function __validate_form() {
    global $video_fields;

    /* Validating the source received from the form */
    $_validated_sources = __validate_sources();
    $sources = $_validated_sources['sources'];
    $incomplete_sources = $_validated_sources['incomplete_sources'];

    try {
        $fields = _validate_array($video_fields);

        /* Maybe it's everything ok with the normal fields, but
           let's find out what happened in the source list
           validation */
        if (sizeof($sources) === 0) {
            throw new ValidationException(array());
        }
        return array('sources' => $sources, 'fields' => $fields);

    } catch (ValidationException $exc) {
        throw new ValidationException(array(
            'errors' => $exc->getErrors(),
            'fields' => $_POST,
            'source_fields' => array_merge($sources, $incomplete_sources),
            'source_incomplete' => $incomplete_sources
        ));
    }
}


function _process_add() {
    $ctx = array();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $ctx;
    }

    global $wpdb;
    $videos_table = $wpdb->prefix . "wpgd_admin_videos";
    $sources_table = $wpdb->prefix . "wpgd_admin_videos_sources";

    /* Validating the rest of the form */
    try {
        $_validated = __validate_form();
        $fields = $_validated['fields'];
        $sources = $_validated['sources'];
    } catch (ValidationException $exc) {
        return $exc->getErrors();
    }

    /* Date field handling */
    /* FIXME: hardcoded date format */
    $date = date_format(date_create_from_format(
        "d/m/Y", $fields['date']), 'Y-m-d H:i:s');

    /* Finally, inserting the video */
    $wpdb->insert(
        $videos_table,
        array(
            'title' => $fields['title'],
            'date' => $date,
            'author' => $fields['author'],
            'license' => $fields['license'],
            'description' => $fields['description'],
            'thumbnail' => $fields['thumbnail'],
            'video_width' => $fields['video_width'],
            'video_height' => $fields['video_height'],
            'status' => isset($_POST['status']) ? '1' : '0'
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
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
        status boolean NOT NULL DEFAULT false,
        thumbnail VARCHAR(256) NOT NULL,
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
