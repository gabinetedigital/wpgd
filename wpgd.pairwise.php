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

require_once("HTTP/Request.php");
require_once('wpgd.conf.php');

function wpgd_pairwise_send_contrib($contrib) {
  global $PAIRWISE_THEMES;
  global $PAIRWISE_CREATE_CHOICE_URL;

  $json = array(
                'id' => $contrib['id'],
                'title' => $contrib['title']);

  $url = $PAIRWISE_CREATE_CHOICE_URL;
  $url = str_replace("<qid>",$PAIRWISE_THEMES[$contrib['theme']], $url);
  $url = str_replace("<text>",urlencode(json_encode($json)), $url);
  $req =& new HTTP_Request($url);
  $req->setMethod(HTTP_REQUEST_METHOD_POST);
  $res = $req->sendRequest();
  return !PEAR::isError($res);
}

$wpgd_pairwise_link = null;
function wpgd_pairwise_db_link() {
    global $wpgd_pairwise_link;
    if ($wpgd_pairwise_link) return $wpgd_pairwise_link;

    $wpgd_pairwise_link = mysql_connect(PAIRWISE_DB_HOST
                                        , PAIRWISE_DB_USER
                                        , PAIRWISE_DB_PASS, true);

    if (!$wpgd_pairwise_link) {
        throw new Exception(mysql_error($wpgd_pairwise_link));
    }

    if (!mysql_select_db(PAIRWISE_DB_NAME, $wpgd_pairwise_link)) {
        throw new Exception(mysql_error($wpgd_pairwise_link));
    }
    return $wpgd_pairwise_link;
}

function wpgd_pairwise_get_var($sql, $argp = array()) {
    global $wpdb;
    $link = wpgd_pairwise_db_link();

    $sql = $wpdb->prepare($sql, $argp);
    $res = mysql_query($sql, $link);
    if (!$res) {
        throw new Exception(mysql_error($link));
    }
    return array_pop(mysql_fetch_array($res, MYSQL_NUM));
}

function wpgd_pairwise_get_results($sql, $argp = array()) {
    global $wpdb;
    $link = wpgd_pairwise_db_link();

    $sql = $wpdb->prepare($sql, $argp);
    $res = mysql_query($sql, $link);

    if (!$res) {
        throw new Exception(mysql_error($link));
    }

    $ret = array();
    while ($row = mysql_fetch_array($res)) {
        $ret[] = $row;
    }

    return $ret;
}

function wpgd_pairwise_get_sorted_by_score($page, $perpage) {
    global $wpdb;

    $link = wpgd_pairwise_db_link();

    $sql_base = "FROM choices
                 ORDER BY score desc";

    $sql = $wpdb->prepare("SELECT id, score, data $sql_base LIMIT %d, %d",
                          array($page*$perpage, $perpage));

    $res = mysql_query($sql, $link);

    if (!$res) {
        throw new Exception(mysql_error($link));
    }

    $ret = array();
    while ($row = mysql_fetch_array($res)) {
        $row['votes'] = wpgd_pairwise_get_choice_votes($row['id']);
        $row['won'] = wpgd_pairwise_get_choice_wins($row['id']);
        $row['lost'] = wpgd_pairwise_get_choice_losts($row['id']);
        $ret[] = $row;
    }

    $sql = "SELECT COUNT(id) $sql_base";
    $count = array_pop(mysql_fetch_array(mysql_query($wpdb->prepare($sql))));
    $total_votes = wpgd_pairwise_get_total_votes();
    return array($ret, $count, $total_votes);
}

function wpgd_pairwise_get_total_votes() {
    global $wpdb;
    $link = wpgd_pairwise_db_link();

    $sql = "SELECT COUNT(votes.id)
            FROM votes";
    $res = mysql_query($sql, $link);

    if (!$res) {
        throw new Exception(mysql_error($link));
    }

    return array_pop(mysql_fetch_array($res));
}

function wpgd_pairwise_get_choice_votes($choice_id) {
    global $wpdb;
    $link = wpgd_pairwise_db_link();

    $sql = $wpdb->prepare("SELECT COUNT(id) as votes
                           FROM votes
                           WHERE choice_id=%d
                            OR loser_choice_id=%d",
                          array($choice_id,$choice_id));

    $res = mysql_query($sql, $link);

    if (!$res) {
        throw new Exception(mysql_error($link));
    }

    return array_pop(mysql_fetch_array($res));
}

function wpgd_pairwise_get_choice_wins($choice_id) {
    global $wpdb;
    $link = wpgd_pairwise_db_link();

    $sql = $wpdb->prepare("SELECT COUNT(votes.id) as votes
                           FROM votes
                           WHERE choice_id=%d",
                          array($choice_id));

    $res = mysql_query($sql, $link);

    if (!$res) {
        throw new Exception(mysql_error($link));
    }

    return array_pop(mysql_fetch_array($res));
}

function wpgd_pairwise_get_choice_losts($choice_id) {
    global $wpdb;
    $link = wpgd_pairwise_db_link();

    $sql = $wpdb->prepare("SELECT COUNT(votes.id) as votes
                           FROM votes
                           WHERE loser_choice_id=%d",
                          array($choice_id));

    $res = mysql_query($sql, $link);

    if (!$res) {
        throw new Exception(mysql_error($link));
    }

    return array_pop(mysql_fetch_array($res));
}

function wpgd_pairwise_get_vote_count_grouped_by_date() {
    $link = wpgd_pairwise_db_link();

    $sql = "SELECT
      year(v.created_at) AS year,
      month(v.created_at) AS month,
      day(v.created_at) AS day,
      date(v.created_at) AS date,
      count(v.id) AS count
    FROM votes AS v GROUP BY DATE(v.created_at);";

    $res = mysql_query($sql, $link);

    if (!$res) {
        throw new Exception(mysql_error($link));
    }


    $ret = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
        $ret[] = $row;
    }

    return $ret;
}
?>
