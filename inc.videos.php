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

function wpgd_videos_get_video($vid) {
    global $wpdb;
    $table = $wpdb->prefix . "wpgd_admin_videos";
    $sql = "
      SELECT
        id, title, date, author, description, thumbnail,
        status, video_width, video_height
      FROM $table
      WHERE id = " . $vid;
    return $wpdb->get_row($wpdb->prepare($sql), ARRAY_A);
}


function wpgd_videos_get_sources($vid) {
    global $wpdb;
    $table = $wpdb->prefix . "wpgd_admin_videos_sources";
    $sql = "SELECT id, url, format FROM $table WHERE video_id = $vid";
    return $wpdb->get_results($wpdb->prepare($sql), ARRAY_A);
}

?>
