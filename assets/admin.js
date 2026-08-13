(function ($) {
	'use strict';

	function closePreview() {
		var $modal = $('#wc-imajiner-preview-modal');
		$modal.attr('hidden', 'hidden');
		$modal.find('iframe').attr('srcdoc', '');
		$('body').removeClass('wc-imajiner-preview-open');
	}

	function openPreview(title, html) {
		var $modal = $('#wc-imajiner-preview-modal');
		$modal.find('.wc-imajiner-preview__title').text(title || '');
		$modal.find('iframe').attr('srcdoc', html || '');
		$modal.removeAttr('hidden');
		$('body').addClass('wc-imajiner-preview-open');
	}

	function previewPayload($form, type) {
		var data = {
			action: type === 'invoice' ? 'wc_imajiner_preview_invoice' : 'wc_imajiner_preview_email',
			nonce: window.wcImajinerAdmin && wcImajinerAdmin.nonce
		};

		if (type === 'email') {
			data.email_id = $form.find('[name="email_id"]').val();
			data.subject = $form.find('[name="subject"]').val();
			data.heading = $form.find('[name="heading"]').val();
			data.body = $form.find('[name="body"]').val();
			data.additional = $form.find('[name="additional"]').val();
			return data;
		}

		data.shop_name = $form.find('[name="shop_name"]').val();
		data.shop_address = $form.find('[name="shop_address"]').val();
		data.shop_phone = $form.find('[name="shop_phone"]').val();
		data.shop_email = $form.find('[name="shop_email"]').val();
		data.document_title = $form.find('[name="document_title"]').val();
		data.intro = $form.find('[name="intro"]').val();
		data.notes = $form.find('[name="notes"]').val();
		return data;
	}

	$(function () {
		$('.wc-imajiner-seeder-form, .wc-imajiner-cron-run').on('submit', function (event) {
			var $form = $(this);
			var type = $form.data('confirm');
			var message = '';

			if (type === 'seeder') {
				message = window.wcImajinerAdmin && wcImajinerAdmin.confirmSeeder;
			} else if (type === 'delete') {
				message = window.wcImajinerAdmin && wcImajinerAdmin.confirmDelete;
			} else if ($form.hasClass('wc-imajiner-cron-run')) {
				message = window.wcImajinerAdmin && wcImajinerAdmin.confirmCron;
			}

			if (message && !window.confirm(message)) {
				event.preventDefault();
			}
		});

		$(document).on('click', '.wc-imajiner-preview-btn', function (event) {
			event.preventDefault();

			var $button = $(this);
			var type = $button.data('preview');
			var $form = $button.closest('form');
			var cfg = window.wcImajinerAdmin || {};

			if (!$form.length || !cfg.ajaxUrl) {
				return;
			}

			$button.prop('disabled', true);
			openPreview(cfg.previewLoading || '…', '');

			$.post(cfg.ajaxUrl, previewPayload($form, type))
				.done(function (response) {
					if (response && response.success && response.data && response.data.html) {
						openPreview(response.data.title, response.data.html);
						return;
					}

					var message = (response && response.data && response.data.message) || cfg.previewError;
					window.alert(message);
					closePreview();
				})
				.fail(function () {
					window.alert(cfg.previewError || 'Error');
					closePreview();
				})
				.always(function () {
					$button.prop('disabled', false);
				});
		});

		$(document).on('click', '[data-preview-close]', function (event) {
			event.preventDefault();
			closePreview();
		});

		$(document).on('keydown', function (event) {
			if (event.key === 'Escape' && !$('#wc-imajiner-preview-modal').attr('hidden')) {
				closePreview();
			}
		});
	});
})(jQuery);
