jQuery(function ($) {
	'use strict';
	/* global reservation_meta_params, jQuery */
	/* eslint-disable no-alert */

	var HTL_Reservation_Meta = {
		init: function () {
			this.edit_guest_fields();
			this.charge_remain_deposit();
			this.show_edit_reservation_total();
			this.update_reservation_total();
			this.datepicker();
			this.show_edit_date();
			this.update_date_total();
		},

		edit_guest_fields: function () {
			$('.edit-address').on('click', function (e) {
				e.preventDefault();

				var _this = $(this);
				var parent = _this.closest('.reservation-data-column');
				var data = parent.find('.guest-data');
				var fields = parent.find('.edit-fields');

				data.hide();
				fields.show();
			});
		},

		show_edit_reservation_total: function () {
			$('.edit-total-icon').on('click', function (e) {
				e.preventDefault();

				$('#total-default').hide();
				$('#total-edit').show();
			});
		},

		update_reservation_total: function () {
			$('.save-total-icon').on('click', function (e) {
				e.preventDefault();

				var oldTotal = parseInt($($('#total-default .amount')[0]).text().match(/\d+(\.\d+)*/)[0].replace(/\./g, ''));
				var newTotal = parseInt($($('#total-edit .new-total')[0]).val());

				if (oldTotal !== newTotal) {
					$('.save-reservation').click();
				} else {
					$('#total-default').show();
					$('#total-edit').hide();
				}
			});
		},

		charge_remain_deposit: function () {
			var form = $('#post');

			$('.charge-remain-deposit').on('click', function (e) {
				e.preventDefault();

				if (window.confirm(reservation_meta_params.i18n_do_remain_deposit_charge)) {
					// Create hidden input and append it to the form
					var input = document.createElement('input');
					input.setAttribute('type', 'hidden');
					input.setAttribute('name', 'hotelier_charge_remain_deposit');
					input.setAttribute('value', 1);
					form.append(input);

					// We can now submit the form
					form.submit();
				}
			});
		},

		datepicker: function () {
			var from_input = $('#date-from');
			var to_input = $('#date-to');

			from_input.datepicker({
				dateFormat: 'yy-mm-dd',
				minDate: 0,
				changeMonth: true
			});

			to_input.datepicker({
				dateFormat: 'yy-mm-dd',
				minDate: 1,
				changeMonth: true
			});
		},

		show_edit_date: function () {
			$('.edit-date').on('click', function (e) {
				e.preventDefault();
				$(this).parent().next(".date-edit").show();
				$(this).parent().hide();
			});
		},

		update_date_total: function () {
			$('.save-date').on('click', function (e) {
				e.preventDefault();

				var oldDate = new Date($(this).parent().prev().text());
				oldDate = oldDate.getFullYear() + "-" + ("00" + (oldDate.getMonth() + 1)).slice(-2) + "-" + ("00" + oldDate.getDate()).slice(-2);

				var newDate = $(this).prev().val();

				if (oldDate !== newDate) {
					$('.save-reservation').click();
				} else {
					$(this).parent().prev(".date-default").show();
					$(this).parent().hide();
				}
			});
		}
	};

	$(document).ready(function () {
		HTL_Reservation_Meta.init();
	});
});
