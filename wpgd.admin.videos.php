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
include_once('inc.servestatic.php');
include_once('inc.videos.php');

$renderer = new WpGdTemplatingRenderer();


/* -- Registering some javascripts and styles used by our interface --  */


add_action('init', function () {
    global $renderer;

    register_taxonomy('video_category', null, array(
        // Hierarchical taxonomy (like categories)
        'hierarchical' => false,
        // This array of options controls the labels displayed in the WordPress Admin UI
        'show_in_nav_menus' => true,
        'show_ui' => true,
        'labels' => array(
            'name' => _x( 'Categoria de Vídeo', 'taxonomy general name' ),
            'singular_name' => _x( 'Categoria', 'taxonomy singular name' ),
            'search_items' =>  __( 'Procurar categorias' ),
            'all_items' => __( 'Todas as categorias' ),
            'parent_item' => __( 'Categoria pai' ),
            'parent_item_colon' => __( 'Categoria pai:' ),
            'edit_item' => __( 'Editar Categoria' ),
            'update_item' => __( 'Alterar Categoria' ),
            'add_new_item' => __( 'Adicionar nova Categoria' ),
            'new_item_name' => __( 'Nome da nova Categoria' ),
            'menu_name' => __( 'Categorias' ),
        ),
        // Control the slugs used for this taxonomy
        'rewrite' => array(
            'slug' => 'video_category', // This controls the base slug that will display before each term
            'with_front' => false, // Don't display the category base before "/video_category/"
            'hierarchical' => true // This will allow URL's like "/video_category/category1/"
        ),
    ));

    /* Nasty wordpress. I can't redirect the user if headers were
       already sent. It means that I'll not be able to do the following
       redirect from the `right' place: _process_remove() */
    if (isset($_GET['screwu'])) {
        wpgd_videos_remove_video($_GET['video_id']);
        header('Location: admin.php?page=wpgd/wpgd.admin.videos.php');
    }

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

    /* -- Registering custom pages -- */

    if (isset($_GET['wpgd/video/sources'])
        && isset($_GET['vid'])
        && isset($_GET['callback'])) {
        $ctx = array('sources' => wpgd_videos_get_sources($_GET['vid']));
        $ctx['callback'] = $_GET['callback'];

        /* Rendering a JSONP call with the sources */
        header('Content-Type: application/x-javascript; charset=UTF-8');
        echo $renderer->render('admin/videos/embed.js', $ctx);
        die();
    }

    if (isset($_GET['wpgd/video/embedjs'])) {
        $sources = home_url() . '?wpgd/video/sources';
        $static = plugins_url('static/videre', __FILE__);
        $full = isset($_GET['full']);
        $deps = array(
            'videre/js/l/flowplayer.js',
            'videre/js/l/video.js',
            'videre/js/avl.js',
            'videre/js/player.js',
        );

        /* User requested full dependency list, let's include jquery and
           an entry point script that calls the `avl.player()' method */
        if ($full) {
            array_unshift($deps, 'videre/js/l/jquery.js');
            array_push($deps, 'js/embed.js');
        }

        /* Rendering the javascript content in the response */
        header('Content-Type: application/x-javascript; charset=UTF-8');
        echo wpgd_servestatic_serve($deps);
        die();
    }

    if (isset($_GET['wpgd/video/embedcss'])) {
        $deps = array('videre/css/video-js.css');
        header('Content-Type: text/css; charset=UTF-8');
        echo wpgd_servestatic_serve($deps);
        die();
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

    // 'edit-tags.php?taxonomy=video_category',
    add_submenu_page(
        $menupage, 'Categories', 'Categories',
        'manage_options', 'edit-tags.php?taxonomy=video_category');

    add_submenu_page(
        $menupage, 'Videos in home', 'Videos in home',
        'manage_options', 'gd-videos-home', 'wpgd_videos_submenu_home');

    add_submenu_page(
        null, 'Edit Video', 'Edit Video',
        'manage_options', 'gd-videos-edit', 'wpgd_videos_submenu_edit');

    add_submenu_page(
        null, 'Remove video', 'Remove video', 'manage_options',
        'gd-videos-remove', 'wpgd_videos_submenu_remove');
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

function wpgd_videos_submenu_remove() {
    global $renderer;
    echo $renderer->render('admin/videos/remove.html', _process_remove());
}

function wpgd_videos_submenu_home() {
    global $renderer;
    echo $renderer->render('admin/videos/home.html', _process_home());
}

function wpgd_videos_submenu_categ() {
    global $renderer;
    echo $renderer->render('edit-tags.php?taxonomy=video_category', _process_home());
}

/* -- Functions that process the requests of the above views -- */


$video_fields = array(
    'title', 'date', 'author', 'license', 'description', 'category',
    'views', 'video_width', 'video_height', 'thumbnail' #, 'subtitle'
);


$source_fields = array(
    'format', 'url'
);


function _process_listing() {
    $ctx = array();
    error_log("PROCESS LISTING >>>>>>>>>>>>>>>>>>>");
    $ctx['listing'] = wpgd_videos_consolidate_category( wpgd_videos_get_videos(null, "date DESC") );
    $ctx['terms'] = get_terms( 'video_category', array( 'hide_empty' => false ) );
    return $ctx;
}


function _process_home() {
    $ctx = array();
    $ctx['listing'] = wpgd_videos_get_highlighted_videos();
    $ctx['terms'] = get_terms( 'video_category', array( 'hide_empty' => false ) );
    return $ctx;
}


function _process_edit() {
    global $wpdb;
    $video_id = $_REQUEST['video_id'];
    $videos_table = $wpdb->prefix . "wpgd_admin_videos";
    $sources_table = $wpdb->prefix . "wpgd_admin_videos_sources";
    $video_categories = $wpdb->prefix . "wpgd_admin_videos_categories";
    $ctx = array('edit' => true);

    /* Getting the video attributes */
    $vd = wpgd_videos_get_video($video_id);
    $ctx['fields'] = $vd[0];

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

        $fields['subtitle'] = $_REQUEST['subtitle'];

        error_log("gravando CATEGORY:".$fields['category']);
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
                'status' => isset($_POST['status']),
                'highlight' => isset($_POST['highlight']),
                // 'category' => $fields['category'],
                'views' => $fields['views'],
                'subtitle' => $fields['subtitle']
            ),
            array('id' => $video_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s'),
            array('%d')
        );

        #recebe as categorias dos videos em uma string com virgulas
        $cats = split(",",$fields['category']);
        #exclui todas categorias dos videos
        $wpdb->query(
            $wpdb->prepare("
                DELETE FROM $video_categories
                WHERE id_video = %d
                ",
                $video_id
            )
        );
        #insere novamente só as que estavam marcadas
        foreach($cats as $c){
            if( ! empty($c) ){
                $wpdb->insert($video_categories,
                    array(
                        'id_video' => $video_id,
                        'id_cat' => $c
                    ),
                    array('%d', '%d')
                );
            }
        }

        $ctx['fields'] = $fields;
        $ctx['fields']['status'] = isset($_POST['status']);
        $ctx['fields']['highlight'] = isset($_POST['highlight']);
        // $ctx['fields']['views'] = $fields['views'];

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

        $ctx["message_ok"] = "Video saved.";
    }

    $ctx['fields']['id'] = $video_id;
    $ctx['terms'] = get_terms( 'video_category', array( 'hide_empty' => false ) );
    return $ctx;
}


function _process_remove() {
    /* Please, note that the actual call for wpgd_videos_remove_video()
       is placed in the "action init" on the top of this module because
       of a nasty redirect issue explained there. Please, don't blame
       me. */
    $ctx = array();
    $vd = wpgd_videos_get_video($_GET['video_id']);
    $ctx['video'] = $vd[0];
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
        error_log("VALIDADE FORM");
        error_log( print_r($fields,true) );
        error_log("VALIDADE FORM");
        return array('sources' => $sources, 'fields' => $fields);

    } catch (ValidationException $exc) {
    	$array = $_POST;

    	foreach ($video_fields as $item) {
		  $clear[$item] = stripslashes(trim($array[$item]));
    	}

        throw new ValidationException(array(
            'errors' => $exc->getErrors(),
            'fields' => $clear,
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
    $video_categories = $wpdb->prefix . "wpgd_admin_videos_categories";

    /* Validating the rest of the form */
    try {
        $_validated = __validate_form();
        $fields = $_validated['fields'];
        $sources = $_validated['sources'];
    } catch (ValidationException $exc) {
        return $exc->getErrors();
    }

    $fields['subtitle'] = $_REQUEST['subtitle'];

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
            'status' => isset($_POST['status']),
            'highlight' => isset($_POST['highlight']),
            // 'category' => isset($_POST['category']),
            'views' => isset($_POST['views']),
            'subtitle' => $fields['subtitle']
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s')
    );

    #recebe as categorias dos videos em uma string com virgulas
    $cats = split(",",$fields['category']);
    #exclui todas categorias dos videos
    $wpdb->query(
        $wpdb->prepare("
            DELETE FROM $video_categories
            WHERE id_video = %d
            ",
            $video_id
        )
    );
    #insere novamente só as que estavam marcadas
    foreach($cats as $c){
        if( ! empty($c) ){
            $wpdb->insert($video_categories,
                array(
                    'id_video' => $video_id,
                    'id_cat' => $c
                ),
                array('%d', '%d')
            );
        }
    }

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


/* -- Shortcode API that provides the [gdvideo] command -- */


function wpgd_admin_videos_shortcode($atts){
    extract(shortcode_atts( array(
        'id' => 'something',
        'width' => '490',
        'height' => '290'
     ), $atts));

    $vd = wpgd_videos_get_video($id);
    $video = $vd[0];
    $sources = wpgd_videos_get_sources($id);

    foreach ( $sources as $s ){
        if( strpos( $s['format'] ,'ogg') > 0 ){
            $url_video_ogg = $s['url'];
        }
        if( strpos( $s['format'] ,'mp4') > 0 ){
            $url_video_mp4 = $s['url'];
        }
        if( strpos( $s['format'] ,'webm') > 0 ){
            $url_video_webm = $s['url'];
        }
    }

    $txtreturn  = "\n<video id=\"$id\" poster=\"".$video['thumbnail']."\" width=\"$width\" height=\"$height\">";

    if( $url_video_ogg != "" ){
        $txtreturn .= "\n   <source src=\"".$url_video_ogg."\" type=\"video/ogg\" />";
    }
    $txtreturn .= "\n   <source src=\"".$url_video_mp4."\" type=\"video/mp4\" />";
    $txtreturn .= "\n   <source src=\"".$url_video_webm."\" type=\"video/webm\" />";
    $txtreturn .= "\nYour browser does not support the video tag.";

    $txtreturn .= "\n</video>\n";



    return $txtreturn;
}
add_shortcode('gdvideo', 'wpgd_admin_videos_shortcode');


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
    $video_categories = $wpdb->prefix . "wpgd_admin_videos_categories";

    $sql = "
    CREATE TABLE  $video_categories  (
        id_video mediumint(9) NOT NULL,
        id_cat   mediumint(9) NOT NULL,
        UNIQUE KEY id_vid_cat (id_video, id_cat)
    );

    CREATE TABLE " . $videos . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        subtitle VARCHAR(400) NULL,
        category mediumint(9) NULL,
        views mediumint(9) NULL,
        date datetime DEFAULT " . $now . " NOT NULL,
        author VARCHAR(200) NOT NULL,
        license tinytext NOT NULL,
        description text NOT NULL,
        status boolean NOT NULL DEFAULT false,
        highlight boolean NOT NULL DEFAULT false,
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

    ALTER TABLE " . $videos . "
      ADD CONSTRAINT FK_video_cat
      FOREIGN KEY (category) REFERENCES " . $categories . "(id);


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
