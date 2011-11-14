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


function wpgd_govp_get_contribs($where=null, $orderby=null, $limit=null) {
    global $wpdb;
    $sql = "
      SELECT c.id, c.title, c.content, c.creation_date, c.theme, c.original, ".
      " c.status, u.display_name ".
      " FROM contrib c, wp_users u ".
      " WHERE c.user_id=u.ID ";
    if (isset($where))
        $sql .= " AND $where ";
    if (isset($orderby))
        $sql .= "ORDER BY $orderby ";
    if (isset($limit))
        $sql .= "LIMIT $limit";
    return $wpdb->get_results($wpdb->prepare($sql));
}

function wpgd_govp_get_contrib($id) {
    global $wpdb;
    $sql = "
      SELECT c.id, c.title, c.content, c.creation_date, c.theme, c.original, ".
      " c.status, u.display_name ".
      " FROM contrib c, wp_users u ".
      " WHERE c.user_id=u.ID AND c.id=%d";
    return array_pop($wpdb->get_results($wpdb->prepare($sql,array($id))));
}


function wpgd_govp_get_contrib_count($where=null) {
    global $wpdb;
    $sql = "SELECT count(id) FROM contrib ";
    if (isset($where))
        $sql .= "WHERE $where ";
    return $wpdb->get_var($wpdb->prepare($sql));
}

?>
