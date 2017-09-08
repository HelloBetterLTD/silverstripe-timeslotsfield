(function($) {

    $.entwine('ss', function($) {

        var dateRange = [], dateFormat = $('.js-occurrence-dates-holder').find('input.startdate').data('jquerydateformat'),
            objDates = {};

        dateFormat = (typeof dateFormat !== 'undefined' && dateFormat) ? dateFormat : 'yy-mm-dd';

        $('.js-occurrence-dates-holder').entwine({
            onmatch: function(e) {
                $('.js-occurrence-row').each(function (i) {
                    var startDate = $(this).find('input.startdate'),
                        endDate = $(this).find('input.enddate');
                    var dates = populateDisabledDates(startDate.val(), endDate.val());
                    objDates[$(this).data('id')] = dates;
                    setGlobalDisableDates();
                    $(this).data('date-range', dates);
                    endDate.data('start-date', startDate.val());

                    updateDatePicker($(this));

                });
            }
        });

        function updateDatePicker(dom) {
            dom.find('input.date').datepicker({
                dateFormat : dateFormat,
                beforeShowDay: function (date) {
                    var occurrenceStart = $(this).data('startDate');
                    var thisDateRange = $(this).closest('.js-occurrence-row').data('dateRange');
                    var dateString = $.datepicker.formatDate(dateFormat, date);

                    var today = new Date();
                    if (occurrenceStart) {
                        var dtOccurrenceStart = new Date(occurrenceStart);
                        if (date.toDateString() == dtOccurrenceStart.toDateString()) {
                            return [true];
                        }
                        if (date < dtOccurrenceStart) {
                            return [false];
                        }
                    }
                    if (date.toDateString() == today.toDateString()) {
                        return [true];
                    }
                    if (thisDateRange.indexOf(dateString) !== -1) {
                        return [true];
                    } else {
                        return [dateRange.indexOf(dateString) == -1];
                    }
                },
                onSelect: function (date, dom) {
                    var occurrenceRow = $(this).closest('.js-occurrence-row'),
                        startDate = occurrenceRow.find('input.startdate'),
                        endDate = occurrenceRow.find('input.enddate');

                    // when start date is changed make the same value for the end date as start date
                    if (dom.input.hasClass('startdate')) {
                        endDate.val(startDate.val());
                    }

                    endDate.data('start-date', startDate.val());
                    var dates = populateDisabledDates(startDate.val(), endDate.val());
                    objDates[occurrenceRow.data('id')] = dates;
                    setGlobalDisableDates();
                    occurrenceRow.data('date-range', dates);
                }
            });
        }

        function setGlobalDisableDates() {
            var tempArray = [];
            $.each(objDates, function(key, value) {
                tempArray = union_arrays(tempArray, value);
            });
            dateRange = tempArray;
        }

        function populateDisabledDates(startDate, endDate) {
            var dates = [];
            for (var d = new Date(startDate); d <= new Date(endDate); d.setDate(d.getDate() + 1)) {
                var date = $.datepicker.formatDate(dateFormat, d);
                if ($.inArray(date, dates) == -1) {
                    dates.push(date)
                }
            }
            return dates;
        }

        $('.js-occurrence-next-day, .js-occurrence-add').entwine({
            onclick: function(e) {
                e.preventDefault();
                var id = '#'+ $(this).closest('.js-occurrence-dates-holder').attr('id');
                copyOccurrenceRow($(id).find(".js-occurrence-row:last"), "day")
            }
        });

        $('.js-occurrence-next-week').entwine({
            onclick: function(e) {
                e.preventDefault();
                var id = '#'+ $(this).closest('.js-occurrence-dates-holder').attr('id');
                copyOccurrenceRow($(id).find(".js-occurrence-row:last"), "week")
            }
        });

        $('.js-occurrence-next-month').entwine({
            onclick: function(e) {
                e.preventDefault();
                var id = '#'+ $(this).closest('.js-occurrence-dates-holder').attr('id');
                copyOccurrenceRow($(id).find(".js-occurrence-row:last"), "month")
            }
        });

        $('.js-occurrence-remove').entwine({
            onmatch: function() {
                var dates = $(this).closest('.js-occurrence-dates'),
                    length = dates.find('.js-occurrence-row').length;
                if (length == 1) {
                    dates.find('.js-occurrence-remove').hide();
                }

            },
            onclick: function(e) {
                e.preventDefault();
                var dates = $(this).closest('.js-occurrence-dates'),
                    row = $(this).closest('.js-occurrence-row');
                delete objDates[row.data('id')];
                setGlobalDisableDates();
                if ($(this).hasClass('js-can-delete'))
                    dates.append('<input type="hidden" name="'+dates.data('name')+'[delete][]" value="'+row.data('id')+'"/>');
                row.remove();
                if (dates.find('.js-occurrence-row').length == 1) {
                    dates.find('.js-occurrence-remove').hide();
                }
            }
        });

        function copyOccurrenceRow(domRow, type) {
            if (!type) {
                type = "day"
            }
            var cloned = domRow.clone(),
                clonedEndDate = cloned.find("input.enddate"),
                clonedStartDate = cloned.find("input.startdate"),
                clonedStartDateValue = clonedStartDate.val(),
                clonedEndDateValue = clonedEndDate.val();
            if (clonedStartDateValue && clonedEndDateValue) {
                var startDate = new Date(clonedStartDateValue),
                    endDate = new Date(clonedEndDateValue);
                if (startDate && endDate) {
                    incrementDate(startDate, endDate, clonedStartDate, clonedEndDate, type);
                }
            }
            cloned.appendTo(domRow.parent());

            var updateField = function(field, fieldType) {
                var f = /\[old\]/,
                    name = $(field).attr('name');
                if (name.match(f)) {
                    name = domRow.data('name')+ '[new]['+fieldType+'][]';
                }
                $(field).attr("name", name);
                $(field).removeAttr("data-id");
                $(field).removeAttr("id");
                $(field).removeClass("js-can-delete");
                $(field).prop("id", Math.floor(Math.random() * 1000));
                $(field).removeClass("hasDatepicker")
            };
            cloned.find("input.startdate").each(function() {
                updateField(this, 'StartDate')
            });
            cloned.find("input.enddate").each(function() {
                updateField(this, 'EndDate')
            });
            cloned.find("input.starttime").each(function() {
                updateField(this, 'StartTime')
            });
            cloned.find("input.endtime").each(function() {
                updateField(this, 'EndTime')
            });
            cloned.find('button.js-occurrence-add').each(function () {
                $(this).html('<span class="ui-button-text">Add another date</span>');
            });
            cloned.find('button.js-occurrence-remove').each(function () {
                $(this).html('<span class="ui-button-text">Remove</span>');
            });
            var id = '#'+ cloned.closest('.js-occurrence-dates-holder').attr('id');
            $(id).find('.js-occurrence-remove').show();

            var dates = populateDisabledDates(clonedStartDate.val(), clonedEndDate.val()),
                dateForDayIndex = new Date(clonedStartDate.val()),
                dayIndex = dateForDayIndex.getTime();
            objDates[dayIndex] = dates;
            cloned.data('id', dayIndex);
            setGlobalDisableDates();
            cloned.data('date-range', dates);
            clonedEndDate.data('start-date', clonedStartDate.val());
            updateDatePicker(cloned);
        }

        function incrementDate(startDate, endDate, inputStartDate, inputEndDate, type) {
            var dtStartDate = new Date(), dtEndDate = new Date();
            var dateDiff = getDiffOfDates(startDate, endDate);
            var weekDiff = parseInt(dateDiff / 7);
            var monthDiff = endDate.getMonth() - startDate.getMonth();
            switch (type) {
                case "day":
                    endDate.setDate(endDate.getDate() + 1);
                    dtStartDate = endDate;
                    dtEndDate = dtStartDate;
                    break;
                case "month":
                    startDate.setMonth(endDate.getMonth() + 1);
                    dtStartDate = startDate;
                    dtEndDate.setDate(endDate.getDate());
                    dtEndDate.setMonth(dtStartDate.getMonth() + monthDiff);
                    dtEndDate.setFullYear(endDate.getFullYear());
                    break;
                case "week":
                    startDate.setDate(startDate.getDate() + ((1 + weekDiff) * 7));
                    dtStartDate = startDate;
                    dtEndDate.setDate(dtStartDate.getDate() + dateDiff);
                    dtEndDate.setMonth(dtStartDate.getMonth() + monthDiff);
                    dtEndDate.setFullYear(dtStartDate.getFullYear());
                    break
            }
            inputStartDate.val($.datepicker.formatDate(dateFormat, dtStartDate));
            inputEndDate.val($.datepicker.formatDate(dateFormat, dtEndDate));
        }

        function getDiffOfDates(firstDate, secondDate) {
            var oneDay = 24*60*60*1000; // hours*minutes*seconds*milliseconds
            return Math.round(Math.abs((firstDate.getTime() - secondDate.getTime())/(oneDay)));
        }

        function union_arrays (x, y) {
            var obj = {};
            for (var i = x.length-1; i >= 0; -- i)
                obj[x[i]] = x[i];
            for (var j = y.length-1; j >= 0; -- j)
                obj[y[j]] = y[j];
            var res = [];
            for (var k in obj) {
                if (obj.hasOwnProperty(k))  // <-- optional
                    res.push(obj[k]);
            }
            return res;
        }

    });
}(jQuery));
