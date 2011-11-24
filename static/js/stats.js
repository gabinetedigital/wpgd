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

(function ($) {
    function getdata($el) {
        return $.parseJSON($el.attr('data'));
    }

    $(function() {
        var data;

        var $chart_byday = $("#chart_byday");
        data = $.map(getdata($chart_byday), function (item) {
            return [[item.day, item.count]];
        });
        $.plot($chart_byday, [data]);


        var $chart_bytheme = $('#chart_bytheme');
        data = $.map(getdata($chart_bytheme), function (item) {
            return { label: item.theme, data: parseInt(item.count)};
        });
        $.plot($chart_bytheme, data, {
            series: {
		pie: { 
		    show: true,
                    combine: {
                        color: '#999',
                        threshold: 0.1
                    },
                    label: {
                        formatter: function(label, series) {
                            console.debug(series);

                            return '<div style="font-size:8pt;'
                                + 'text-align:center;'
                                + 'color:'
                                + series.color + ';">'
                                + label + '<br>'
                                + Math.round(series.percent) + '% ('
                                + series.data[0][1]
                                + ')</div>';
                        }
                    }
		}
	    },
            legend: {
                show: false
            }
        });


        var $chart_bythemedate = $('#chart_bythemedate');
        data = (function () {
            var themes = {};
            $(getdata($chart_bythemedate)).each(function (index, item) {
                var theme = item.theme || 'null';
                if (themes[theme] === undefined) {
                    /* First time looking for this theme, it does not exist yet */
                    themes[theme] = [];
                } else {
                    /* Just filling the already created array */
                    themes[theme].push([ item.day, item.count ]);
                }
            });

            /* Preparing data to be passed to flot */
            var data = [];
            for (var i in themes) {
                data.push({
                    label: i,
                    data: themes[i]
                });
            };
            return data;
        })();

        $.plot($chart_bythemedate, data, {
            series: {
                lines: { show: true },
                points: { show: true }
            },

            legend: { noColumns: 2 },
            xaxis: { tickDecimals: 0 },
            yaxis: { min: 0 },

            selection: { mode: "x" }
        });
    });
})(jQuery);
