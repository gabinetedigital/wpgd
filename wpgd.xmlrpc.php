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

/**
 * A shortcut to log the user in before making any `wp_' call and escape
 * all the received arguments of an exposed method.
 *
 * Borrowed from `exapi'
 *
 * @params array $args Arguments that will be escaped with a wordpress
 *  xmlrpc utility
 */
function _wpgd_method_header(&$args) {
    // We don't like smart-ass people
    global $wp_xmlrpc_server;
    $wp_xmlrpc_server->escape( $args );

    // getting rid of blog_id
    array_shift($args);

    // Reading the attribute list
    $username = array_shift($args);
    $password = array_shift($args);

    // All methods in this API are being protected
    if (!$user = $wp_xmlrpc_server->login($username, $password))
        return $wp_xmlrpc_server->error;
    return $args;
}


/**
 * A wrapper for the function that lists all highlighted videos
 *
 * @param array $args Holds parameters to be passed to the actuall
 *  function being wrapped and, in this case, you can pass the number of
 *  highlights you want.
 */
function wpgd_getHighlightedVideos($args) {
    if (!is_array($args = _wpgd_method_header($args))) {
        return $args;
    }
    $limit = null;
    if (isset($args[1]))
        $limit = $args[1];
    return wpgd_videos_get_highlighted_videos($limit);
}


/**
 * A wrapper for the function that lists videos
 *
 * @param array $args Holds parameters to be passed to the actuall
 *  function being wrapped. Please see the wpgd_videos_get_videos()
 *  function for a deeper description.
 */
function wpgd_getVideos($args) {
    if (!is_array($args = _wpgd_method_header($args))) {
        return $args;
    }

    /* Just making sure that we can keep rocking */
    if (!isset($args[0]))
        $args[0] = array();

    /* These are the parameters that we're waiting for */
    $names = array('where', 'orderby', 'limit', 'offset');
    $params = array();

    /* Getting params from the $args array */
    for ($i = 0; $i < count($names); $i++) {
        if (array_key_exists($names[$i], $args[0])) {
            $params[$i] = $args[0][$names[$i]];
        } else {
            $params[$i] = null;
        }
    }

    return call_user_func_array('wpgd_videos_get_videos', $params);
}


/**
 * A wrapper for the function that gets a video by its id
 *
 * @param array $args Holds parameters to be passed to the actuall
 *  function being wrapped and, in this case, you can pass the video id.
 */
function wpgd_getVideo($args) {
    if (!is_array($args = _wpgd_method_header($args))) {
        return $args;
    }
    return wpgd_videos_get_video($args[1]);
}


/**
 * A wrapper for the function that lists the sources of a video
 *
 * @param array $args Holds parameters to be passed to the actuall
 *  function being wrapped and, in this case, you can pass the video id.
 */
function wpgd_getVideoSources($args) {
    if (!is_array($args = _wpgd_method_header($args))) {
        return $args;
    }
    return wpgd_videos_get_sources($args[1]);
}


/* ---- Pairwise API ---- */


/**
 * A wrapper for the function that gets pairwise choices sorted by its
 * scores.
 *
 * @param array $args Holds parameters to be passed to the actuall
 *  function being wrapped and, in this case, you need to pass the
 *  `page', the `perpage' and `theme' params.
 */
function pairwise_getSortedByScore($args) {
    if (!is_array($args = _wpgd_method_header($args))) {
        return $args;
    }

    return wpgd_pairwise_get_sorted_by_score($args[1], $args[2], $args[3]);
}



/* Filter that registers our methods in the wordpress xmlrpc provider */
add_filter('xmlrpc_methods', function ($methods) {
    $methods['wpgd.getVideo'] = 'wpgd_getVideo';
    $methods['wpgd.getVideos'] = 'wpgd_getVideos';
    $methods['wpgd.getHighlightedVideos'] = 'wpgd_getHighlightedVideos';
    $methods['wpgd.getVideoSources'] = 'wpgd_getVideoSources';

    $methods['pairwise.getSortedByScore'] = 'pairwise_getSortedByScore';
    return $methods;
});

?>
