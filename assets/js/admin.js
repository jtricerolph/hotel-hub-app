/**
 * Hotel Hub App - Admin JavaScript
 *
 * Handles admin interface interactions, image uploads, and integration testing.
 *
 * @package Hotel_Hub_App
 */

(function($) {
    'use strict';

    const HotelHubAdmin = {
        init: function() {
            this.bindEvents();
            this.initImageUploads();
            this.initColorPicker();
        },

        bindEvents: function() {
            // Tab switching
            $('.hha-tab-link').on('click', (e) => this.switchTab(e));

            // Integration testing
            $('.hha-test-connection').on('click', (e) => this.testConnection(e));

            // Theme mode change
            $('#theme_mode').on('change', (e) => this.toggleCustomColor(e));
        },

        switchTab: function(e) {
            e.preventDefault();
            const $link = $(e.currentTarget);
            const target = $link.attr('href');

            $('.hha-tab-link').removeClass('active');
            $link.addClass('active');

            $('.hha-tab-content').removeClass('active');
            $(target).addClass('active');
        },

        initImageUploads: function() {
            let mediaUploader;

            $('.hha-upload-image-btn').on('click', function(e) {
                e.preventDefault();

                const $btn = $(this);
                const targetId = $btn.data('target');
                const $input = $('#' + targetId);
                const $preview = $btn.siblings('.hha-image-preview');

                // Create media uploader
                mediaUploader = wp.media({
                    title: 'Choose Image',
                    button: {
                        text: 'Select'
                    },
                    multiple: false
                });

                // On select
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();

                    // Set attachment ID
                    $input.val(attachment.id);

                    // Show preview
                    $preview.html(`<img src="${attachment.url}" alt="" style="max-width: 250px;">`);

                    // Update button text
                    $btn.text('Change Image');

                    // Show remove button
                    if ($btn.siblings('.hha-remove-image-btn').length === 0) {
                        $btn.after('<button type="button" class="button hha-remove-image-btn" data-target="' + targetId + '">Remove</button>');
                    }
                });

                mediaUploader.open();
            });

            // Remove image
            $(document).on('click', '.hha-remove-image-btn', function(e) {
                e.preventDefault();

                const $btn = $(this);
                const targetId = $btn.data('target');
                const $input = $('#' + targetId);
                const $preview = $btn.siblings('.hha-image-preview');
                const $uploadBtn = $btn.siblings('.hha-upload-image-btn');

                // Clear input
                $input.val('');

                // Clear preview
                $preview.empty();

                // Update button text
                $uploadBtn.text('Upload Image');

                // Remove button
                $btn.remove();
            });
        },

        initColorPicker: function() {
            if ($.fn.wpColorPicker) {
                $('.hha-color-picker').wpColorPicker();
            }
        },

        toggleCustomColor: function(e) {
            const $select = $(e.currentTarget);

            if ($select.val() === 'custom') {
                $('.hha-custom-color-row').show();
            } else {
                $('.hha-custom-color-row').hide();
            }
        },

        testConnection: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const integrationType = $btn.data('type');
            const $form = $btn.closest('form');

            // Prepare data
            let data = {
                action: 'hha_test_' + integrationType,
                nonce: hhaAdminData.nonce
            };

            if (integrationType === 'newbook') {
                data.username = $form.find('#newbook_username').val();
                data.password = $form.find('#newbook_password').val();
                data.api_key = $form.find('#newbook_api_key').val();
                data.region = $form.find('#newbook_region').val();

                if (!data.username || !data.password || !data.api_key) {
                    alert('Please fill in all required fields');
                    return;
                }
            } else if (integrationType === 'resos') {
                data.api_key = $form.find('#resos_api_key').val();

                if (!data.api_key) {
                    alert('Please enter API key');
                    return;
                }
            }

            // Show loading
            const originalText = $btn.text();
            $btn.text('Testing...').prop('disabled', true);

            // Remove previous results
            $('.hha-test-result').remove();

            // Send request
            $.post(ajaxurl, data)
                .done((response) => {
                    let resultHtml;

                    if (response.success) {
                        resultHtml = `<div class="hha-test-result success">${response.data.message}</div>`;
                    } else {
                        resultHtml = `<div class="hha-test-result error">${response.data.message}</div>`;
                    }

                    $btn.after(resultHtml);
                })
                .fail(() => {
                    $btn.after('<div class="hha-test-result error">Network error. Please try again.</div>');
                })
                .always(() => {
                    $btn.text(originalText).prop('disabled', false);

                    // Auto-hide result after 5 seconds
                    setTimeout(() => {
                        $('.hha-test-result').fadeOut(() => {
                            $('.hha-test-result').remove();
                        });
                    }, 5000);
                });
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        HotelHubAdmin.init();
    });

})(jQuery);
