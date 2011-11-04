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

class ValidationException extends Exception {
    protected $errors;

    public function __construct($errors=array()) {
        parent::__construct('Validation Error', 0);
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
}


/* -- Generic validation function -- */


function _validate_post($fields) {
    $clear = array();
    $errors = array();

    foreach ($fields as $item) {
        $clear[$item] = trim($_POST[$item]);
        if ($clear[$item] === '') {
            $errors[] = $item;
        }
    }

    if (sizeof($errors) > 0) {
        throw new ValidationException($errors);
    }

    return $clear;
}

