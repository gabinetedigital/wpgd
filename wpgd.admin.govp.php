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
include_once('inc.govp.php');

add_action('init', function () {
    if (is_admin()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script(
            'wpgd-contrib',
            plugins_url('static/js/contrib.js', __FILE__));

        wp_enqueue_style(
            'wpgd-contrib-css',
            plugins_url('static/css/contrib.css', __FILE__));
    }
  }
);


add_action('admin_menu', function () {
    $menupage = __FILE__;

    add_menu_page(
        'Governador Pergunta', 'Governador Pergunta', 'administrator',
        $menupage, 'wpgd_govp_main');
});


function wpgd_govp_main() {
    global $renderer;
    $perpage = 50;
    $page = (int) (isset($_GET["paged"]) ? $_GET["paged"] : '0');

    $ctx = array();
    $ctx['listing'] = wpgd_govp_get_contribs($_GET["sort"],$_GET['paged'],$perpage);


    //odin sent me...
    foreach($ctx['listing'] as $obj) {
        if ($obj->moderation == 0) { //contrib registered through the portal
            $obj->title =  iconv('UTF-8', 'iso-8859-1', $obj->title);
            $obj->content =  iconv('UTF-8', 'iso-8859-1', $obj->content);
            $obj->display_name =  iconv('UTF-8', 'iso-8859-1', $obj->display_name);
        } //else: contrib registered through WP
    }

    $ctx['count'] = wpgd_govp_get_contrib_count();
    $ctx['siteurl'] = get_bloginfo('siteurl');
    $ctx['sortby'] = get_query_var("sort");
    $ctx['paged'] =  $page;
    $ctx['numpages'] = ceil($ctx['count'] / $perpage);
    $ctx['perpage'] = $perpage;
    $ctx['pageurl'] = remove_query_arg("sort");
    $ctx['pageurl'] = remove_query_arg("paged");
    echo $renderer->render('admin/govp/listing.html', $ctx);
}

function wpgd_update_contrib() {
    //backup the original author's contribution before updating it

    global $wpdb;
    $org = wpgd_govp_get_contrib($_POST['data']['id']);

    switch ($_POST['data']['field']) {
    case 'content':
        if (strlen($org->original) == 0) {
            $wpdb->update("contrib",
                          array('original' => $org->content),
                          array('id' => $_POST['data']['id']));
        }
        die($wpdb->update("contrib",
                          array('content' => $_POST['data']['content']),
                          array('id' => $_POST['data']['id'])));
        break;
    case 'title':
        die($wpdb->update("contrib",
                          array('title' => $_POST['data']['title']),
                          array('id' => $_POST['data']['id'])));
        break;

    case 'status':
        die($wpdb->update("contrib",
                          array('status' => !($org->status)),
                          array('id' => $_POST['data']['id'])));
        break;
    case 'parent':
        die($wpdb->update("contrib",
                          array('parent' => $_POST['data']['parent']),
                          array('id' => $_POST['data']['id'])));
        break;
    case 'theme':
        die($wpdb->update("contrib",
                          array('theme' => $_POST['data']['theme']),
                          array('id' => $_POST['data']['id'])));
        break;
    }
}
add_action('wp_ajax_update_contrib', 'wpgd_update_contrib');

function wpgd_insert_contrib() {
    global $wpdb;
    $current_user = wp_get_current_user();
    die($wpdb->insert("contrib",
                      array('parent' => 0,
                            'theme' => $_POST['data']['theme'],
                            'title' => $_POST['data']['title'],
                            'content' => $_POST['data']['content'],
                            'user_id' => $current_user->ID,
                            'moderation' => true)));
}

function wpgd_delete_contrib() {
    global $wpdb;
    $id = $_POST['data']['id'];
    $org = wpgd_govp_get_contrib($id);
    if ($org->moderation) {
        $wpdb->query("DELETE FROM contrib WHERE id = '$id'");
    } else {
        $wpdb->update("contrib",
                      array('enabled' => 0),
                      array('id' => $id));
    }

    //reparent
    die($wpdb->update("contrib",
                      array('parent' => 0),
                      array('parent' => $id)));

}

add_action('wp_ajax_insert_contrib', 'wpgd_insert_contrib');
add_action('wp_ajax_delete_contrib', 'wpgd_delete_contrib');
?>
