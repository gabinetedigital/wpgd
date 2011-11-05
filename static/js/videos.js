/* Copyright (C) 2011  Governo do Estado do Rio Grande do Sul
 *
 *   Author: Lincoln de Sousa <lincoln@gg.rs.gov.br>
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

var wpgdVideo = (function ($) {
    $(function() {
        /* FIXME: hardcoded date format */
        $('.date').datepicker({ dateFormat: 'dd/mm/yy' });
    });

    function Video() { }
    Video.prototype = {
        addSource: function () {
            var $clone = $($('.sourcerow')[0]).clone();
            $clone.find('input[type=text]').val('');
            $clone.find('input[type=hidden]').val('');
            $clone.find('select').val('');
            $clone.appendTo('#sources');
            return false;
        },

        removeSource: function (base) {
            if ($('.sourcerow').length > 1) {
                $(base).parentsUntil('.sourcerow').parent().remove();
            }
            return false;
        }
    };

    return new Video();
})(jQuery);
