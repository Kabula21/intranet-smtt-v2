(function ($) {
    'use strict';

    $(function () {
        var selectButton = $('#smttTourSelectImage');
        var removeButton = $('#smttTourRemoveImage');
        var imageId = $('#smtt_tour_image_id');
        var preview = $('#smttTourImagePreview');
        var orientation = $('#smttTourImageOrientation');

        if (!selectButton.length || typeof wp === 'undefined' || !wp.media) {
            return;
        }

        selectButton.on('click', function (event) {
            event.preventDefault();

            var frame = wp.media({
                title: 'Selecionar imagem de boas-vindas',
                button: { text: 'Usar no Tour' },
                library: { type: 'image' },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                var src = attachment.sizes && attachment.sizes.medium_large
                    ? attachment.sizes.medium_large.url
                    : (attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url);
                var width = parseInt(attachment.width || 0, 10);
                var height = parseInt(attachment.height || 0, 10);
                var label = 'Quadrada';

                if (width > height * 1.15) label = 'Horizontal';
                if (height > width * 1.15) label = 'Vertical';

                imageId.val(attachment.id);
                preview.addClass('has-image').html('<img src="' + src + '" alt="">');
                orientation.text('Orientação detectada: ' + label + '. O layout se adapta automaticamente no desktop e no celular.');
                removeButton.prop('disabled', false);
            });

            frame.open();
        });

        removeButton.on('click', function (event) {
            event.preventDefault();
            imageId.val('0');
            preview.removeClass('has-image').html('<span class="smtt-tour-image-empty">Nenhuma imagem selecionada. O Tour também funciona perfeitamente só com texto.</span>');
            orientation.text('Aceita imagem horizontal, vertical ou quadrada. O layout se adapta automaticamente.');
            removeButton.prop('disabled', true);
        });
    });
})(jQuery);
