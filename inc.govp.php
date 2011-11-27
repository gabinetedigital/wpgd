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

function wpgd_govp_get_contribs($sortby, $page, $perpage) {
    $page = (strlen($page) == 0) ?  '0' : $page;
    $offset = $page * $perpage;

    $sortfields = array(
                        'id' => 'c.id' ,
                        'status' => 'c.status',
                        'theme' => 'c.theme',
                        'date'  => 'c.creation_date',
                        'author' => 'u.display_name',
                        'title' => 'c.title');
    if (isset($sortfields[$sortby])) {
        $sortfield = $sortfields[$sortby];
    } else {
        $sortfield = 'c.id';
    }

    function index_of($arr, $id) {
        $f = array_filter($arr,
                          function($x) use ($id) {
                              return $x['id'] == $id;
                          });
        return key($f);
    }

    global $wpdb;
    $sql = "
      SELECT c.id, c.title, c.content, c.creation_date, c.theme, c.original, ".
        " c.status, u.display_name, c.parent, c.moderation, u.ID as user_id ".
        " FROM contrib c, wp_users u ".
        " WHERE c.user_id=u.ID AND c.enabled=1 order by $sortfield LIMIT $offset, $perpage";

    $results = $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);

    $roots = array();
    $children = array();
    foreach ($results as $r) {
        if ($r['parent'] == 0) {
            $roots[] = $r;
        } else {
            $children[] = $r;
        }
    }
    foreach($children as $c) {
        $idx = index_of($roots, $c['parent']);
        array_splice($roots, $idx+1, 0, 'An uninteresting value as markplace');
        $roots[$idx+1] = $c;
    }
    return array_map(function($x) { return (object)$x; },$roots);
}


function wpgd_govp_get_contrib($id) {
    global $wpdb;
    $sql = "
      SELECT c.id, c.title, c.content, c.creation_date, c.theme, c.original, ".
      " c.status, u.display_name, c.parent, c.moderation ".
      " FROM contrib c, wp_users u ".
      " WHERE c.user_id=u.ID AND c.enabled=true AND c.id=%d";
    return array_pop($wpdb->get_results($wpdb->prepare($sql,array($id))));
}


function wpgd_govp_get_contrib_count() {
    global $wpdb;
    $sql = "SELECT count(id) FROM contrib WHERE enabled=1 ";
    return $wpdb->get_var($wpdb->prepare($sql));
}


function wpgd_govp_get_contrib_count_grouped_by_date() {
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


function wpgd_govp_get_contrib_count_grouped_by_theme() {
    global $wpdb;
    $sql = "SELECT
      c.theme, count(c.id) AS count FROM contrib AS c
    GROUP BY c.theme;";
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_govp_get_contrib_count_grouped_by_themedate() {
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
?>
