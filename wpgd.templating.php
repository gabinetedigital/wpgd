<?php /* -*- Mode: php; c-basic-offset:4; -*- */
/* Copyright (C) 2011  Lincoln de Sousa <lincoln@comum.org>
 * Copyright (C) 2011  Governo do Estado do Rio Grande do Sul
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

include 'Twig/Autoloader.php';
Twig_Autoloader::register();

/**
 * A template renderer engine based on the `twig' library.
 */
class WpGdTemplatingRenderer {
    private $twig = null;

    function __construct() {
        $path = join('/', array(dirname(__FILE__), 'templates'));
        $loader = new Twig_Loader_Filesystem($path);
        $this->twig = new Twig_Environment($loader);
    }

    public function render($templateName, $context=array()) {
        $template = $this->twig->loadTemplate($templateName);
        return $template->render($context);
    }
}
