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
    $ctx = array();
    $ctx['listing'] = wpgd_govp_get_contribs();
    $ctx['count'] = wpgd_govp_get_contrib_count();
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
    }
}
add_action('wp_ajax_update_contrib', 'wpgd_update_contrib');
?>
