jQuery(function ($) {
	'use strict';
	/* global jQuery */

	var HTL_Stats = {
		init: function () {
			this.datepicker();
			this.tabella();
		},

		datepicker: function () {
			$('.datepicker').datepicker({
				dateFormat: 'yy-mm-dd'
			});
		},

		tabella: function () {
		var myTabella = new Tabella(
            document.getElementById('stats-table'),
            {   
                tableHeader: [
                    ['2014-12-14', '2014-12-20']
                    ,['2014-12-21', '2015-1-10']
                    ,['2015-2-1', '2015-3-7']
                    ,['2015-3-8', '2015-3-21']
                    ,['2015-3-22', '2015-4-7']
                ],
                rows : [
                    {
                        rowHeader: 'Single bed room',
                        rowVal: [[190 , 210, 210, 204, 180],[190 , 210, 210, 204, 180]],
                        rowDesc : ['1 persona', '2 persone']
                    },
                    {
                        rowHeader: 'Double bed room',
                        rowVal: [[190 , 210, 210, 204, 180]],
                        rowDesc : ['1 persona']
                    },
                    {
                        rowHeader: 'Suite',
                        rowVal: [[190 , 210, 210, 204, 180]],
                        rowDesc : ['1 persona']
                    }

                ]
            });
		}
	};

	$(document).ready(function () {
		HTL_Stats.init();
	});
});