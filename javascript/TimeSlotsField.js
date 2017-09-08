(function($) {

    $.entwine('ss', function($) {

        $('.js-time-add').entwine({
            onclick: function(e) {
                $(this).closest('form').addClass('changed');
                e.preventDefault();
                var id = '#'+ $(this).closest('.js-time-slots-holder').attr('id');
                copyOccurrenceRow($(id).find(".js-time-row:last"))
            }
        });

        $('.js-time-remove').entwine({
            onmatch: function() {
                var dates = $(this).closest('.js-time-slots'),
                    length = dates.find('.js-time-row').length;
                if (length == 1) {
                    dates.find('.js-time-remove').hide();
                }

            },
            onclick: function(e) {
                $(this).closest('form').addClass('changed');
                e.preventDefault();
                var dates = $(this).closest('.js-time-slots'),
                    row = $(this).closest('.js-time-row');
                if ($(this).hasClass('js-can-delete'))
                    dates.append('<input type="hidden" name="'+dates.data('name')+'[delete][]" value="'+row.data('id')+'"/>');
                row.remove();
                if (dates.find('.js-time-row').length == 1) {
                    dates.find('.js-time-remove').hide();
                }
            }
        });

        function copyOccurrenceRow(domRow) {
            var cloned = domRow.clone();
            cloned.find("input.time").val('');
            cloned.find("input.time").removeClass('timepicker-applied');
            cloned.appendTo(domRow.parent());

            cloned.find("input.time").each(function() {
                var f = /\[old\]/,
                    name = $(this).attr('name');
                if (name.match(f)) {
                    name = domRow.data('name')+ '[new][]';
                }
                $(this).attr("name", name);
                $(this).removeAttr("data-id");
                $(this).removeAttr("id");
                $(this).removeClass("js-can-delete timepicker-applied hasTimepicker");
                $(this).prop("id", Math.floor(Math.random() * 1000));
            });
            cloned.find('button.js-time-add').each(function () {
                $(this).html('<span class="ui-button-text">+</span>');
            });
            cloned.find('button.js-time-remove').each(function () {
                $(this).html('<span class="ui-button-text">-</span>');
            });
            var id = '#'+ cloned.closest('.js-time-slots-holder').attr('id');
            $(id).find('.js-time-remove').show();
        }

    });
}(jQuery));
