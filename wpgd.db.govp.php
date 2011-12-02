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

function _wpgd_enter_encoding() {
    global $wpdb;
    if (mysql_client_encoding($wpdb->dbh) == 'utf8') {
        mysql_set_charset("latin1", $wpdb->dbh);
    }
}

function _wpgd_leave_encoding() {
    global $wpdb;
    mysql_set_charset("utf8", $wpdb->dbh);
}

function wpgd_db_get_contribs($sortby,
                              $page,
                              $perpage,
                              $theme,
                              $status,
                              $s,
                              $filter = null) {
    $page = (strlen($page) == 0) ?  '0' : $page;
    $offset = $page * $perpage;
    $sortfields = array(
        'id' => 'contrib.id' ,
        'status' => 'contrib.status',
        'theme' => 'contrib.theme',
        'date'  => 'contrib.creation_date',
        'author' => 'user.display_name',
        'title' => 'contrib.title'
    );
    if (isset($sortfields[$sortby])) {
        $sortfield = $sortfields[$sortby];
    } else {
        $sortfield = 'contrib.id';
    }

    function index_of($arr, $id) {
        $f = array_filter($arr, function($x) use ($id) {
            return $x['id'] == $id;
        });
        if (count($f) == 0) {
            return -1;
        } else {
            return key($f);
        }
    }

    /* Handling filters */
    $filter = $filter ? " AND ($filter) " : "";
    $filters = array();
    if (!empty($theme)) {
        array_push($filters, "contrib.theme = '$theme'");
    }
    if (!empty($status)) {
        array_push($filters, "contrib.status = $status");
    }
    if (count($filters) > 0) {
        $filter .= "AND (" . join(" AND ", $filters) . ")";
    }

    /* Handling the text search */
    $search = "";
    if (!empty($_GET['s'])) {
        $s = '%%' . $_GET['s'] . '%%';
        $search = "AND (title LIKE '$s' OR content LIKE '$s')";
    }

    global $wpdb;
    $sql_head = "
      SELECT
          contrib.id, contrib.title, contrib.content, contrib.creation_date,
          contrib.theme, contrib.original, contrib.status, user.display_name,
          contrib.parent, contrib.moderation, user.ID as user_id
    ";

    $sql_base ="
      FROM
          contrib, wp_users user
      WHERE
          (contrib.user_id=user.ID AND contrib.enabled=1) $filter $search
      ORDER BY $sortfield
    ";

    $sql_main = $sql_head . $sql_base . "LIMIT $offset, $perpage";
    $sql_count = "SELECT COUNT(contrib.id) AS total " . $sql_base;

    _wpgd_enter_encoding();
    $results = $wpdb->get_results($wpdb->prepare($sql_main), ARRAY_A);
    $count = $wpdb->get_var($wpdb->prepare($sql_count));
    _wpgd_leave_encoding();

    return array($results, $count);
}

function wpgd_db_get_unique_contribs($sortby
                                     , $page
                                     , $perpage
                                     , $theme
                                     , $status
                                     , $s) {

    return wpgd_db_get_contribs($sortby
                                , $page
                                , $perpage
                                , $theme
                                , $status
                                , $s
                                , "contrib.parent=0");
}


function wpgd_db_get_contrib($id) {
    global $wpdb;
    $sql = "
      SELECT c.id, c.title, c.content, c.creation_date, c.theme, c.original, ".
      " c.status, u.display_name, c.parent, c.moderation ".
      " FROM contrib c, wp_users u ".
      " WHERE c.user_id=u.ID AND c.enabled=true AND c.id=%d";

    _wpgd_enter_encoding();
    $res = $wpdb->get_var($wpdb->prepare($sql,array($id)));
    _wpgd_leave_encoding();

    return $res;
}


function wpgd_db_get_contrib_count() {
    global $wpdb;
    $sql = "SELECT count(id) FROM contrib WHERE enabled=1 ";
    return $wpdb->get_var($wpdb->prepare($sql));
}


function wpgd_db_get_contrib_count_grouped_by_date() {
    global $wpdb;
    $sql = "SELECT
      year(c.creation_date) AS year,
      month(c.creation_date) AS month,
      day(c.creation_date) AS day,
      date(c.creation_date) AS date,
      count(c.id) AS count
    FROM contrib AS c GROUP BY DATE(c.creation_date);";
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_db_get_contrib_count_grouped_by_theme() {
    global $wpdb;
    $sql = "SELECT
      c.theme, count(c.id) AS count FROM contrib AS c
    GROUP BY c.theme;";
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_db_get_contrib_count_grouped_by_themedate() {
    global $wpdb;
    $sql = "SELECT
      c.theme,
      date(c.creation_date) AS date,
      count(c.id) AS count,
      year(c.creation_date) AS year,
      month(c.creation_date) AS month,
      day(c.creation_date) AS day,
      date(c.creation_date) AS date
    FROM contrib AS c GROUP BY c.theme, date(c.creation_date);";
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_db_get_theme_counts() {
    global $wpdb;
    $ret = array();
    $sql = "SELECT COUNT(c.id) count, theme FROM contrib c GROUP BY c.theme";
    foreach ($wpdb->get_results($wpdb->prepare($sql), ARRAY_A) as $row) {
        $ret[$row['theme']] = $row['count'];
    }
    return $ret;
}

//contrib functions

function wpgd_contrib_has_duplicates($contrib) {
    global $wpdb;

    if ($contrib['parent'] > 0) return true;

    $sql = "SELECT COUNT(*)
            FROM contrib
            WHERE parent=${contrib[id]} AND enabled=1";

    return $wpdb->get_var($wpdb->prepare($sql)) > 0;
}


function wpgd_contrib_get_duplicates($contrib) {
    global $wpdb;

    $sql = "SELECT *
            FROM contrib
            WHERE parent=${contrib[id]}
              AND enabled=1";

    _wpgd_enter_encoding();
    $res = $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
    _wpgd_leave_encoding();
    return $res;
}


function wpgd_contrib_get_all_duplicates($contrib) {
    global $wpdb;

    /*
       the $parent contrib
       + contribs with same parent <> 0 (ie. siblings)
       + every other contrib with parent = $parent <> 0
       + every child contrib (everyone whose parent=$id)
       - self
    */

    $sql = "SELECT *
            FROM contrib
            WHERE (
                 id=${contrib[parent]}
               OR
                 ( parent=${contrib[parent]}
                   AND parent <> 0)
               OR
                 parent=${contrib[id]})
             AND id<>${contrib[id]}
             AND enabled=1";
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_contrib_get_parent($contrib) {
    global $wpdb;

    if ($contrib['parent'] == 0)
        return null;
    $sql = "SELECT * FROM contrib
            WHERE id=${contrib[parent]} AND enabled=1";
    return $wpdb->get_var($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_contrib_get_owner($contrib) {
    return get_userdata($contrib['user_id']);
}


function wpgd_contrib_get_authors($contrib) {

    function push_unique($users, $user) {
        foreach($users as $u) {
            if ($u->ID == $user->ID) return;
        }
        array_push($users, $user);
    }

    $dups = wpgd_contrib_get_duplicates($contrib);
    $ret = array(get_userdata($contrib['user_id']));

    foreach($dups as $c) {
        push_unique($ret, get_userdata($c['user_id']));
    }
    return $ret;
}

?>
