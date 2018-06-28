jQuery(function ($) {
	'use strict';
	/* global jQuery */

	var HTL_Stats = {
		init: function () {
			this.datepicker();
		},

		datepicker: function () {
			$('.datepicker').datepicker({
				dateFormat: 'yy-mm-dd'
			});
		},
	};

	$(document).ready(function () {
		HTL_Stats.init();
	});
});