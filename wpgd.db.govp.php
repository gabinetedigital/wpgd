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

function wpgd_db_get_contribs($sortby, $page, $perpage, $theme, $status, $s) {
    $page = (strlen($page) == 0) ?  '0' : $page;
    $offset = $page * $perpage;
    $sortfields = array(
        'id' => 'c.id' ,
        'status' => 'c.status',
        'theme' => 'c.theme',
        'date'  => 'c.creation_date',
        'author' => 'u.display_name',
        'title' => 'c.title'
    );
    if (isset($sortfields[$sortby])) {
        $sortfield = $sortfields[$sortby];
    } else {
        $sortfield = 'c.id';
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
    $filter = "";
    $filters = array();
    if (!empty($theme)) {
        array_push($filters, "c.theme = '$theme'");
    }
    if (!empty($status)) {
        array_push($filters, "c.status = $status");
    }
    if (count($filters) > 0) {
        $filter = "AND (" . join(" AND ", $filters) . ")";
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
          c.id, c.title, c.content, c.creation_date, c.theme, c.original,
          c.status, u.display_name, c.parent, c.part, c.moderation, u.ID as user_id
    ";

    $sql_base ="
      FROM
          contrib c, wp_users u
      WHERE
          (c.user_id=u.ID AND c.enabled=1) $filter $search
      ORDER BY $sortfield
    ";

    $sql_main = $sql_head . $sql_base . "LIMIT $offset, $perpage";
    $sql_count = "SELECT COUNT(c.id) AS total " . $sql_base;

    if (mysql_client_encoding($wpdb->dbh) == 'utf8') {
        mysql_set_charset("latin1", $wpdb->dbh);
    }
    $results = $wpdb->get_results($wpdb->prepare($sql_main), ARRAY_A);
    $count = $wpdb->get_var($wpdb->prepare($sql_count));
    mysql_set_charset("utf8", $wpdb->dbh);

    return array($results, $count);
}


function wpgd_db_get_contrib($id) {
    global $wpdb;
    $sql = "
      SELECT c.id, c.title, c.content, c.creation_date, c.theme, c.original, ".
      " c.status, u.display_name, c.parent, c.part, c.moderation ".
      " FROM contrib c, wp_users u ".
      " WHERE c.user_id=u.ID AND c.enabled=true AND c.id=%d";

    if(mysql_client_encoding($wpdb->dbh) == 'utf8') {
        mysql_set_charset( "latin1", $wpdb->dbh );
    }
    $res = $wpdb->get_results($wpdb->prepare($sql,array($id)));
    mysql_set_charset( "utf8", $wpdb->dbh );

    return count($res) == 1 ? $res[0] : null;
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
?>
