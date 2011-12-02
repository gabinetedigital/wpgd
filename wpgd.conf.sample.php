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

$PAIRWISE_THEMES = array(
                         'cuidado' => 1,
                         'familia' => 2,
                         'emergencia' => 3,
                         'medicamentos' => 4,
                         'regional' => 5);

$PAIRWISE_CREATE_CHOICE_URL = "http://pairuser:pairpass@localhost:4000/questions/<qid>/choices.xml?choice%5Bdata%5D=<text>";


define('PAIRWISE_DB_HOST', 'host');
define('PAIRWISE_DB_NAME', 'name');
define('PAIRWISE_DB_USER', 'user');
define('PAIRWISE_DB_PASS', 'pass');

?>