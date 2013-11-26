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
    error_log(">>>>>>>>>>>>>>>> wpgd_videos_get_videos");
    $videos = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "
      SELECT
        v.id, v.title, v.date, v.author, v.description, v.thumbnail, c.id_cat category, t.name category_name,
        v.status, v.highlight, v.video_width, v.video_height, v.views, v.subtitle
      FROM $videos v
        LEFT JOIN `wp_wpgd_admin_videos_categories` c
        LEFT JOIN `wp_terms` t on c.id_cat = t.term_id on v.id = c.id_video ";
    if (isset($where))
        $sql .= "WHERE $where ";
    if (isset($orderby))
        $sql .= "ORDER BY $orderby ";
    if (isset($limit))
        $sql .= "LIMIT $limit ";
    if (isset($offset))
        $sql .= "OFFSET $offset ";
    error_log($sql);
    return $wpdb->get_results($wpdb->prepare($sql));
}

function wpgd_videos_get_videos_categories($where=null, $orderby=null, $limit=null, $offset=null) {
    // global $wpdb;
    $categories = get_terms( 'video_category', 'orderby=count&hide_empty=0' );
    return $categories;
}

function wpgd_videos_get_highlighted_videos($limit=null) {
    error_log(">>>>>>>>>>>>>>>> wpgd_videos_get_highlighted_videos");
    return wpgd_videos_get_videos("highlight=1", "date DESC", $limit);
}

function wpgd_videos_get_bycategory($category=null, $orderby=null, $limit=null, $offset=null) {
    // return wpgd_videos_get_videos("category=".$category, $orderby, $limit, $offset);
    error_log(">>>>>>>>>>>>>>>> wpgd_videos_get_bycategory");
    global $wpdb;
    $videos = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "
        SELECT v.id, v.title, v.date, v.author, v.description, v.thumbnail,
               c.id_cat category, t.name category_name, v.status, v.highlight,
               v.video_width, v.video_height, v.views, v.subtitle
        FROM $videos v
        LEFT JOIN `wp_wpgd_admin_videos_categories` c
        LEFT JOIN `wp_terms` t on c.id_cat = t.term_id on v.id = c.id_video ";
    if (isset($category))
        $sql .= "WHERE c.id_cat = $category ";
    if (isset($orderby))
        $sql .= "ORDER BY $orderby ";
    if (isset($limit))
        $sql .= "LIMIT $limit ";
    if (isset($offset))
        $sql .= "OFFSET $offset ";
    error_log($sql);
    return $wpdb->get_results($wpdb->prepare($sql));

}

function wpgd_videos_get_video($vid) {
    global $wpdb;
    $table = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "
      SELECT
        v.id, v.title, v.date, v.author, v.description, v.thumbnail, c.id_cat category, t.name category_name,
        v.status, v.video_width, v.video_height, v.views, v.highlight, v.subtitle
      FROM $table v
        LEFT JOIN `wp_wpgd_admin_videos_categories` c
        LEFT JOIN `wp_terms` t on c.id_cat = t.term_id on v.id = c.id_video
      WHERE v.id = " . $vid;
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
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

function wpgd_videos_consolidate_category($videos){
    #This method consolidates all videos with your categories in only one line by video.
    $todos = array();
    foreach ($videos as $video){
        if( !isset($todos[$video->id]) ){
            $todos[$video->id] = clone $video;
        }else{
            $video->category = $todos[$video->id]->category .",". $video->category;
            $video->category_name = $todos[$video->id]->category_name . "," . $video->category_name;
            unset($todos[$video->id]);
            $todos[$video->id] = $video;
        }
    }
    return $todos;
}

?>
