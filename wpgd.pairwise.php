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
                'id' => $contrib->id,
                'title' => $contrib->title);

  $url = $PAIRWISE_CREATE_CHOICE_URL;
  $url = str_replace("<qid>",$PAIRWISE_THEMES[$contrib->theme], $url);
  $url = str_replace("<text>",urlencode(json_encode($json)), $url);
  $req =& new HTTP_Request($url);
  $req->setMethod(HTTP_REQUEST_METHOD_POST);
  $res = $req->sendRequest();
  return !PEAR::isError($res);
}

?>