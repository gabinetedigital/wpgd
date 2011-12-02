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
include_once('wpgd.db.govp.php');
include_once('wpgd.pairwise.php');

define('WPGD_CONTRIBS_PER_PAGE', 50);

$themes = array(
    'cuidado', 'familia', 'emergencia',
    'medicamentos', 'regional'
);

add_action('init', function () {
    if (is_admin()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
    }
});


add_action('admin_menu', function () {
    $menupage = __FILE__;

    $contribs = add_menu_page(
        'Governador Pergunta', 'Governador Pergunta', 'moderate_contrib',
        $menupage, 'wpgd_govp_main');

    $stats = add_submenu_page(
        $menupage, 'Stats', 'Stats',
        'moderate_contrib', 'gd-admin-stats', 'wpgd_govp_stats');

    /* Loading javascript */
    add_action('admin_enqueue_scripts', function ($hooksufix) use ($contribs, $stats) {
        switch ($hooksufix) {
        case $contribs:
            wp_enqueue_script(
                'wpgd-contrib',
                plugins_url('static/js/contrib.js', __FILE__));

            wp_enqueue_style(
                'wpgd-contrib-css',
                plugins_url('static/css/contrib.css', __FILE__));
            break;

        case $stats:
            wp_enqueue_script(
                'flot',
                plugins_url('static/js/jquery.flot.min.js', __FILE__));

            wp_enqueue_script(
                'flot-pie',
                plugins_url('static/js/jquery.flot.pie.js', __FILE__));

            wp_enqueue_script(
               'stats',
               plugins_url('static/js/stats.js', __FILE__));
            break;
        }
    });
});


function wpgd__gen_qstring($defaults) {
    $data = array();
    foreach (array('paged', 'theme', 'status', 'sort') as $v) {
        if (!empty($_GET[$v])) {
            array_push($data, "$v=" . $_GET[$v]);
        }
    }

    foreach($defaults as $k => $v) {
        $data[$k] = $v;
    }

    return join("&", $data);
}


/**
 * Returns sorted contributions to render on the html table
 */
function wpgd__sorted_contribs($page) {
    list($contribs, $count) = wpgd_db_get_contribs(
        $_GET["sort"], $page, WPGD_CONTRIBS_PER_PAGE,
        $_GET['theme'], $_GET['status'], $_GET['s']
    );
    return array($contribs, $count);
}


/**
 * Helper function to get the proper css class of a contribution row
 */
function wpgd__get_class($contrib) {
    $klass = array();
    if ($contrib['parent'] > 0) {
        array_push($klass, "is-duplicated");
        array_push($klass, "duplication-of-${contrib[parent]}");
    } else if (wpgd_contrib_get_parents($contrib)){
        array_push($klass, "wpgd-part");
    } else {
        if ($contrib['status'] == 1) {
            array_push($klass, "wpgd-approved");
        } else {
            array_push($klass, "wpgd-disapproved");
        }
    }
    return join(" ", $klass);
}


function wpgd__get_parents_string($contrib) {
    $parents = array();
    foreach (wpgd_contrib_get_parents($contrib) as $c) {
        array_push($parents, $c['id']);
    }
    return join(" ", $parents);
}


function wpgd__can_be_approved($contrib) {
    return count(wpgd_contrib_get_parents($contrib)) == 0 &&
        $contrib['parent'] == 0;
}


function wpgd_govp_main() {
    global $renderer;
    global $themes;
    $page = (int) (isset($_GET["paged"]) ? $_GET["paged"] : '1');

    $ctx = array();

    list($ctx['listing'], $ctx['count']) = wpgd__sorted_contribs($page-1);

    $ctx['themes'] = $themes;
    $ctx['s'] = $_GET['s'];
    $ctx['theme'] = $_GET['theme'];
    $ctx['status'] = $_GET['status'];
    $ctx['themecounts'] = wpgd_db_get_theme_counts();
    $ctx['total_count'] = wpgd_db_get_contrib_count();
    $ctx['siteurl'] = get_bloginfo('siteurl');
    $ctx['sortby'] = get_query_var("sort");
    $ctx['paged'] =  $page;
    $ctx['numpages'] = ceil($ctx['count'] / WPGD_CONTRIBS_PER_PAGE);
    $ctx['perpage'] = WPGD_CONTRIBS_PER_PAGE;
    $ctx['pageurl'] = remove_query_arg("sort");
    $ctx['pageurl'] = remove_query_arg("paged");
    echo $renderer->render('admin/govp/listing.html', $ctx);
}


function wpgd_govp_stats() {
    global $renderer;
    $ctx = array();
    $ctx['chart_byday'] =
        json_encode(wpgd_db_get_contrib_count_grouped_by_date());
    $ctx['chart_bytheme'] =
        json_encode(wpgd_db_get_contrib_count_grouped_by_theme());
    $ctx['chart_bythemedate'] =
        json_encode(wpgd_db_get_contrib_count_grouped_by_themedate());
    echo $renderer->render('admin/govp/stats.html', $ctx);
}


function wpgd_update_contrib() {
    //backup the original author's contribution before updating it

    global $wpdb;
    $org = wpgd_db_get_contrib($_POST['data']['id']);

    _wpgd_enter_encoding();
    switch ($_POST['data']['field']) {
    case 'content':
        if (strlen($org['original']) == 0) {
            $wpdb->update("contrib",
                          array('original' => $org['content']),
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
        $val = !($org['status']);
        $wpdb->update("contrib",
                      array('status' => $val),
                      array('id' => $_POST['data']['id']));
        if ($val) {
            die(wpgd_pairwise_send_contrib($org) ? "ok" : "error");
        }
        die("ok");
        break;
    case 'parent':
        if ($_POST['data']['parent'] != "0") {
            $parent = wpgd_db_get_contrib($_POST['data']['parent']);

            if ($parent == null) {
                die("not-found");
            }
        }
        die($wpdb->update("contrib",
                          array('parent' => $_POST['data']['parent']),
                          array('id' => $_POST['data']['id'])));
        break;
    case 'part':
        if ($_POST['data']['part'] != "0") {
            /* If the string comes empty the user want to nuke all
             * children contribs, let's grant his/her wish */
            if (empty($_POST['data']['part'])) {
                foreach (wpgd_contrib_get_parents($org) as $parent) {
                    wpgd_contrib_remove_part($parent, $org);
                }
                die('ok');
            }

            $pids = explode(" ", $_POST['data']['part']);

            /* FIXME: avoid calling _get_contrib() twice for each
             * contrib in this function */
            foreach ($pids as $pid) {
                $parent = wpgd_db_get_contrib($pid);
                if ($parent == null) {
                    die("not-found");
                }
            }

            /* Removing all parts previously added */
            wpgd_contrib_remove_all_parts($org);

            /* Now we're sure that everything's good, so we can insert
             * the new parts. */
            foreach ($pids as $pid) {
                $parent = wpgd_db_get_contrib($pid);
                wpgd_contrib_append_part($parent, $org);
            }
        }
        die("ok");
        break;
    case 'theme':
        die($wpdb->update("contrib",
                          array('theme' => $_POST['data']['theme']),
                          array('id' => $_POST['data']['id'])));
        break;
    }
    _wpgd_leave_encoding();
}


function wpgd_insert_contrib() {
    global $wpdb;
    _wpgd_enter_encoding();
    $current_user = wp_get_current_user();

    $ret = $wpdb->insert("contrib",
                         array('parent' => 0,
                               'theme' => $_POST['data']['theme'],
                               'title' => $_POST['data']['title'],
                               'content' => $_POST['data']['content'],
                               'user_id' => $current_user->ID,
                               'part' => $_POST['data']['part'],
                               'enabled' => 1,
                               'status' => 0,
                               'moderation' => true));
    _wpgd_leave_encoding();
    die($ret);
}


function wpgd_delete_contrib() {
    global $wpdb;
    $id = $_POST['data']['id'];
    $org = wpgd_db_get_contrib($id);
    if ($org['moderation']) {
        $wpdb->query("DELETE FROM contrib WHERE id = '$id'");
    } else {
        $wpdb->update("contrib",
                      array('enabled' => 0),
                      array('id' => $id));
    }

    // reset parent
    $wpdb->update("contrib",
                  array('parent' => 0),
                  array('parent' => $id));

    // reset parts
    die($wpdb->update("contrib",
                      array('part' => 0),
                      array('part' => $id)));

}

add_action('wp_ajax_insert_contrib', 'wpgd_insert_contrib');
add_action('wp_ajax_update_contrib', 'wpgd_update_contrib');
add_action('wp_ajax_delete_contrib', 'wpgd_delete_contrib');
?>
