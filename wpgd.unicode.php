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


/* Data created from flask app is saved in utf8, the php side must do
 * the same. And we also must show these strings correctly.
 *
 * Once I'm not a PHP guru, I have isolated everything that I needed to
 * convert strings from one encoding to another in this file, cause if
 * (when) I need to change it, things we'll be easier to find.
 */


/**
 * Function to convert an utf-8 string to an utf-8 one without errors
 * (by using the translit version of a problematic char). If the input
 * string is not utf8.
 */
function wpgd_u($str) {
    return mb_detect_encoding($str) == "UTF-8" ?
        iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str) : $str;
}


/**
 * Function that converts an iso-8859-1 string to utf8 
 */
function wpgd_e($str) {
    return utf8_encode($str);
}

?>
