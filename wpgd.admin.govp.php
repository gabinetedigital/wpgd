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

include_once('wpgd.templating.php');
include_once('inc.govp.php');

add_action('admin_menu', function () {
    $menupage = __FILE__;

    add_menu_page(
        'Governador Pergunta', 'Governador Pergunta', 'administrator',
        $menupage, 'wpgd_govp_main');
});


function wpgd_govp_main() {
    global $renderer;
    $ctx = array();
    $ctx['listing'] = wpgd_govp_get_contribs();
    $ctx['count'] = wpgd_govp_get_contrib_count();
    echo $renderer->render('admin/govp/listing.html', $ctx);
}

?>
