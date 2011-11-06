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


/**
 * Add new image sizes for posts that fits to the GD site
 */
function wpgd_thumbs_init_sizes() {
    if (function_exists('add_image_size')) {
        // Adding the image size present in the `slideshow' box also in
        // the index page.
        add_image_size('slideshow', 325, 180, true);

        // Here we set the default post thumbnail dimensions, that
        // is the size that we use in the `news' box present in the
        // index page.
        add_image_size('newsbox', 120, 110, true);

        // This is the second type type of image size present in the
        // news box, the one that takes all the width of the box.
        add_image_size('widenewsbox', 600, 180, true);
    }
}

?>
