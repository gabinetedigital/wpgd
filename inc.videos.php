<?php /* -*- Mode: php; c-basic-offset:4; -*- */
/* Copyright (C) 2011  Lincoln de Sousa <lincoln@comum.org>
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


function wpgd_videos_get_videos($where=null, $orderby=null, $limit=null, $offset=null) {
    global $wpdb;
    $videos = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "
      SELECT
        v.id, v.title, v.date, v.author, v.description, v.thumbnail, v.category, t.name category_name,
        v.status, v.highlight, v.video_width, v.video_height, v.views, v.subtitle
      FROM $videos v left join `wp_terms` t on v.category = t.term_id ";
    if (isset($where))
        $sql .= "WHERE $where ";
    if (isset($orderby))
        $sql .= "ORDER BY $orderby ";
    if (isset($limit))
        $sql .= "LIMIT $limit ";
    if (isset($offset))
        $sql .= "OFFSET $offset ";
    // error_log($sql);
    return $wpdb->get_results($wpdb->prepare($sql));
}

function wpgd_videos_get_videos_categories($where=null, $orderby=null, $limit=null, $offset=null) {
    // global $wpdb;
    $categories = get_terms( 'video_category', 'orderby=count&hide_empty=0' );
    return $categories;
}

function wpgd_videos_get_highlighted_videos($limit=null) {
    return wpgd_videos_get_videos("highlight=1", "date DESC", $limit);
}

function wpgd_videos_get_bycategory($category=null, $orderby=null, $limit=null, $offset=null) {
    return wpgd_videos_get_videos("category=".$category, $orderby, $limit, $offset);
}

function wpgd_videos_get_video($vid) {
    global $wpdb;
    $table = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "
      SELECT
        v.id, v.title, v.date, v.author, v.description, v.thumbnail, v.category, t.name category_name,
        v.status, v.video_width, v.video_height, v.views, v.highlight, v.subtitle
      FROM $table  v left join `wp_terms` t on v.category = t.term_id 
      WHERE id = " . $vid;
    return $wpdb->get_row($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_videos_get_sources($vid) {
    global $wpdb;
    $table = $wpdb->prefix . "wpgd_admin_videos_sources";
    $sql = "SELECT id, url, REPLACE(format, '\\\\', '') as format
      FROM $table WHERE video_id = $vid";
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_videos_remove_video($vid) {
    global $wpdb;
    $table = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "DELETE FROM $table WHERE id = %s";
    return $wpdb->query($wpdb->prepare($sql, $vid));
}

?>
