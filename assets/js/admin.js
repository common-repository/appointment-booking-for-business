function dateFromString(date) {
    return new Date(date);
    //return removeTZ(new Date(date));
}

function dateToUFString(date) {
    return (date.getUTCMonth()+1)+'/'+date.getUTCDate()+'/'+date.getUTCFullYear();
}

function dateToISOWithoutTZ (date) {
    return date.toISOString().substring(0,10);
    //var d = removeTZ(date);
    //return d.getUTCFullYear()+'-'+(d.getUTCMonth()+1)+'-'+d.getUTCDate();
}

// Admin calendar selections
(function($){
    function calendarStructure(year, month) {
        var startDate = new Date(Date.UTC(year, month, 1)),
            endDate = new Date(Date.UTC(year, month+1, 0)),
            weekday = startDate.getUTCDay(),
            cD = new Date(),
            currentDate = new Date(Date.UTC(cD.getUTCFullYear(),cD.getUTCMonth(),cD.getUTCDate()));

        var weekdays = ['Su','Mo','Tu','We','Th','Fr','Sa'],
            monthNames = ["January", "February", "March", "April", "May", "June",
              "July", "August", "September", "October", "November", "December"
            ],
            html;

        html = '<div class="calendar-block">'+
            '<h3>'+monthNames[month]+' '+year+'</h3>'+
            '<div class="table-wrapper"><table cellpadding="0" cellspacing="0"><tr>'+weekdays.map(function(d){return '<th>'+d+'</th>';}).join('')+'</tr>';

        if (weekday>0)
            html += '<tr><td colspan='+weekday+'></td>';
        for (var i = startDate.getUTCDate(); i <= endDate.getUTCDate(); i++) {
            var dateObj = new Date(Date.UTC(year, month, i)),
                dateStr = dateToISOWithoutTZ(dateObj),
                addClass = dateObj>=currentDate?'day':'';
            html += '<td class="'+addClass+'" rel="'+dateStr+'">'+i+'</td>';
            if (++weekday>6 && i !== endDate.getUTCDate()) {
                html += '</tr><tr>';
                weekday = 0;
            }
        }
        if (weekday > 0 && weekday < 6)
            html += '<td colspan="'+(6-weekday)+'"></td>';

        html += '</tr></table></div></div>';
        return html;
    }

    function selectedDateStructure(){
        return '<div class="selected-date"></div>';
    }

    function printSelectedDate(container, from, to, apply, cancel) {
        var selectedDateContainer = container.find(".selected-date");
        var html = dateToUFString(from)+' - '+dateToUFString(to);
        html += '<button class="button button-primary apply" type="button">Mark unavailable</button> <button type="button" class="button cancel">Cancel</button>'
        selectedDateContainer.html(html);

        selectedDateContainer.find('button.cancel').on('click',function(){cancel(container);});
        selectedDateContainer.find('button.apply').on('click',function(){apply(container);});
    }

    function removeSelectedDate(container, from, to) {
        container.find(".selected-date").html('');
    }

    function elementDate(el){
        return dateFromString(el.attr('rel'));
    }

    function dayByDate(date) {
        var d = dateToISOWithoutTZ(date);
        return $(".day[rel='"+d+"']");
    }

    function removeClass(container, className) {
        container.find('.day').each(function(){
            $(this).removeClass(className);
        });
    }

    function classFromTo(container, from, to, className, notRemove) {
        if (to<from){
            var t = from;
            from = to;
            to = t;
        }
        //from = removeTZ(from);
        //to = removeTZ(to);
        notRemove = notRemove||false;
        container.find('.day').each(function () {
            var date = elementDate($(this));
            if (date>=from && date <= to) {
                $(this).addClass(className);
            } else if (!notRemove)
                $(this).removeClass(className);
        });
    }

    function removeHover(container) {
        hoverFromTo(container, new Date(0,0,0), new Date(0,0,0));
    }

    function removeInterval(container) {
        removeHover(container);
        intervalFromTo(container, new Date(0,0,0), new Date(0,0,0));
    }

    function hoverFromTo(container, from, to) {
        classFromTo(container, from, to, 'hover');
    }

    function intervalFromTo(container, from, to) {
        removeHover(container);
        classFromTo(container, from, to, 'interval');
    }

    function removeSelections(container) {
        removeClass(container, 'selected');
    }
    function selectedFromTo(container, from, to) {
        classFromTo(container, from, to, 'selected', true);
    }

    function printCalendar(container, selections) {
        var currentDate = new Date(),
            currentSelectedStart = null,
            currentSelectedEnd = null,
            currentLeftCalendar = new Date();

        selections = selections || [];

        function printSelections(container) {
            removeSelections(container);
            for (var i in selections) {
                selectedFromTo(container, selections[i][0], selections[i][1]);
            }
        }

        function getSelectionIndex(date) {
            for (var i in selections) {
                if (date>=selections[i][0] && date<=selections[i][1])
                    return i;
            }
            return null;
        }

        function getSelection(date) {
            var ind = getSelectionIndex(date);
            if (!ind)return null;
            return selections[ind];
        }

        function isInSelection(date) {
            return getSelection(date)!==null;
        }

        function isCrossSelection(from, to) {
            if (from>to){
                var t = from;
                from = to;
                to = t;
            }
            if (isInSelection(from) || isInSelection(to))return true;
            for (var i in selections) {
                if (from<selections[i][0]&&to>selections[i][1])return true;
            }
            return false;
        }

        function cancelInterval(container) {
            removeInterval(container);
            removeSelectedDate(container);
            if (currentSelectedStart)
                dayByDate(currentSelectedStart).removeClass('active');
            if (currentSelectedEnd)
                dayByDate(currentSelectedEnd).removeClass('active');
            currentSelectedStart = null;
            currentSelectedEnd = null;
        }

        function applyInterval(container) {
            selections.push([currentSelectedStart,currentSelectedEnd]);
            cancelInterval(container);
            printSelections(container);
        }

        function activateCalendarClicks (container) {
            container.find('.day')
            .on('click',function(){
                var day = $(this),
                    date = elementDate(day);

                if (isInSelection(date)){
                    var selInd = getSelectionIndex(date),
                        sel = selections[selInd];
                    if (confirm("Remove "+sel[0].toLocaleDateString()+'-'+sel[1].toLocaleDateString()+"?")) {
                        selections.splice(selInd,1);
                        printSelections(container);
                    }
                    return;
                }

                if (currentSelectedStart && !currentSelectedEnd) {
                    if (isCrossSelection(currentSelectedStart, date))return;

                    day.addClass('active');
                    currentSelectedEnd = date;
                    if (currentSelectedEnd < currentSelectedStart){
                        var t = currentSelectedEnd;
                        currentSelectedEnd = currentSelectedStart;
                        currentSelectedStart = t;
                    }
                    intervalFromTo(container, currentSelectedStart, currentSelectedEnd);
                    printSelectedDate(container, currentSelectedStart, currentSelectedEnd, applyInterval, cancelInterval);
                } else if (currentSelectedStart && currentSelectedEnd) {
                    if (isCrossSelection(currentSelectedStart, date))return;
                    if (date < currentSelectedStart) {
                        dayByDate(currentSelectedStart).removeClass('active');
                        currentSelectedStart = date;
                        day.addClass('active');
                        intervalFromTo(container, currentSelectedStart, currentSelectedEnd);
                        printSelectedDate(container, currentSelectedStart, currentSelectedEnd, applyInterval, cancelInterval);
                    }
                    if (date > currentSelectedStart) {
                        dayByDate(currentSelectedEnd).removeClass('active');
                        currentSelectedEnd = date;
                        day.addClass('active');
                        intervalFromTo(container, currentSelectedStart, currentSelectedEnd);
                        printSelectedDate(container, currentSelectedStart, currentSelectedEnd, applyInterval, cancelInterval);
                    }
                    if (date.getTime()===currentSelectedEnd.getTime()) {
                        if (date.getTime()!==currentSelectedStart.getTime())
                            day.removeClass('active');
                        currentSelectedEnd = null;
                        cancelInterval(container);
                    } else if (date.getTime()===currentSelectedStart.getTime()){
                        day.removeClass('active');
                        currentSelectedStart = currentSelectedEnd;
                        currentSelectedEnd = null;
                        cancelInterval(container);
                    }
                } else {
                    currentSelectedStart = date;
                    day.addClass('active');
                }
            })
            .on('mouseover',function(){
                if (currentSelectedStart && !currentSelectedEnd) {
                    if (elementDate($(this)) >= currentSelectedStart)
                        hoverFromTo(container, currentSelectedStart, elementDate($(this)));
                    else
                        hoverFromTo(container, elementDate($(this)), currentSelectedStart);
                }
            });
        }

        function isFirstAvailMonth (cal) {
            var cD = new Date();
            return cal.getUTCFullYear()===cD.getUTCFullYear() && cal.getUTCMonth()===cD.getUTCMonth();
        }

        function isLastAvailMonth (cal) {
            var c = new Date(),
                cD = new Date(c.getUTCFullYear(), c.getUTCMonth()+11, 1);
            return cal.getUTCFullYear()===cD.getUTCFullYear() && cal.getUTCMonth()===cD.getUTCMonth();
        }

        function switchMonth(container, leftOrRight){
            if (leftOrRight && isFirstAvailMonth(currentLeftCalendar) ||
                !leftOrRight && isLastAvailMonth(currentLeftCalendar)
            ) return null;
            currentLeftCalendar = new Date(Date.UTC(currentLeftCalendar.getUTCFullYear(),currentLeftCalendar.getUTCMonth()+(!leftOrRight<<1)-1));
            pasteCalendars(container);
        }

        function pasteCalendars (container) {
            var datePlus1Month = new Date(Date.UTC(currentLeftCalendar.getUTCFullYear(),currentLeftCalendar.getUTCMonth()+1));
            container.find('.calendars').html(
                calendarStructure(currentLeftCalendar.getUTCFullYear(),currentLeftCalendar.getUTCMonth()) +
                calendarStructure(datePlus1Month.getUTCFullYear(),datePlus1Month.getUTCMonth())
            );
            activateCalendarClicks(container);

            if (selections.length)
                printSelections(container);
        }

        container.html(
            '<div class="switch-month-container">' +
                '<i class="fa fa-arrow-left switch-month switch-left"></i>' +
                '<i class="fa fa-arrow-right switch-month switch-right"></i>' +
            '</div>' +
            '<div class="calendars"></div>' +
            selectedDateStructure()
        );

        container.find('.switch-month').on('click',function(){
            switchMonth(container, $(this).hasClass('switch-left'));
        });

        pasteCalendars(container);

        return function(){
            return selections;
        };
    }

    window.printCalendar = printCalendar;
})(jQuery);
// Admin calendar selections end

// Edit calendar functions
(function($){
    function addInterval(interval) {
        var elem = $(".inactive-time-row-template").clone();
        elem.removeClass('inactive-time-row-template').addClass('inactive-interval');
        elem.appendTo(".inactive-times-container").show();
        elem.find('[name="inactive_time_from_"]').attr('name',"inactive_time_from[]");
        elem.find('[name="inactive_time_to_"]').attr('name',"inactive_time_to[]");
        elem.find('[name="inactive_time_from[]"] option[value='+interval[0]+']').attr('selected','selected');
        elem.find('[name="inactive_time_to[]"] option[value='+interval[1]+']').attr('selected','selected');
        elem.find('.remove-inactive').on('click',function(){
            $(this).closest(".inactive-interval").remove();
        });
    }
    function initInactiveIntervals(intervals) {
        $(".inactive-times-container").html();
        for (var i in intervals) {
            addInterval(intervals[i]);
        }
        $(".add-inactive-interval").on('click',function(){
            addInterval([0,0]);
        });
    }
    function editCalendar(id, formData, selections) {
        id = id || 0;
        formData.append("action", "gcal_edit_calendar");
        formData.append("calendar_id", id);
        formData.append("_nonce", bkforb_gcal_admin._nonce);
        formData.append("selections", JSON.stringify(selections.map(function(d){return d.map(function(c){return dateToISOWithoutTZ(c);});})));
        return new Promise(function(resolve, reject){
            $.ajax({
                type: "POST",
                url: bkforb_gcal_admin.ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (resp) {
                    if (resp['success'] === 'success') {
                        resolve(resp);
                    } else {
                        reject(resp['reason']);
                    }
                }
            })
        });
    }

    window.editCalendar = editCalendar;
    window.initInactiveIntervals = initInactiveIntervals;
})(jQuery);
// Edit calendar functions end