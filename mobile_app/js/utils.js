Function.prototype.bind = Function.prototype.bind || function (oThis) {
    if (typeof this !== "function") {
        throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
    }
    var aArgs = Array.prototype.slice.call(arguments, 1),
        fToBind = this,
        fNOP = function () {},
        fBound = function () {
            return fToBind.apply(this instanceof fNOP && oThis
                    ? this
                    : oThis,
                aArgs.concat(Array.prototype.slice.call(arguments)));
        };
    fNOP.prototype = this.prototype;
    fBound.prototype = new fNOP();
    return fBound;
};

function fillTemplate(template, values){
    var matches = template.match(/\$\$(\S)*?\$\$/gi);
    if(!matches){
        return template;
    }
    $.each(matches, function (i, s) {
        var field = s.substr(2, s.length - 4);
        //var value = field == 'uploads_url' ? DFXAPI.UPLOADS_URL : values[field];
        template = template.replace(s, values[field]);
    });

    return template;
}
function removeItemFromArrayByIndex (array, indexStart, indexEnd) {
    var result;

    result = array.splice(0, indexStart);

    var secondItem = indexStart;

    if( indexEnd )
        secondItem = indexEnd;

    for(var i = secondItem - 1; i < array.length ; i++) {
        result.push(array[i]);
    }
    return result;
}


function isEmpty(obj) {
    // null and undefined are "empty"
    if (obj == null) return true;

    // Assume if it has a length property with a non-zero value
    // that that property is correct.
    if (obj.length > 0)    return false;
    if (obj.length === 0)  return true;

    // Otherwise, does it have any properties of its own?
    // Note that this doesn't handle
    // toString and valueOf enumeration bugs in IE < 9
    for (var key in obj) {
        if (hasOwnProperty.call(obj, key)) return false;
    }

    return true;
};
function randomColor() {
    var r=Math.floor(Math.random() * (256)),
        g=Math.floor(Math.random() * (256)),
        b=Math.floor(Math.random() * (256));
    return '#' + r.toString(16) + g.toString(16) + b.toString(16);
}
function ValidUrl(str) {
    var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
        '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
    if(!pattern.test(str)) {
        return false;
    } else {
        return true;
    }
}


function isHex(h) {
    return /^#[0-9A-F]{6}$/i.test(h);
}
function hexToRgb(hex) {
    // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
    var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
    hex = hex.replace(shorthandRegex, function(m, r, g, b) {
        return r + r + g + g + b + b;
    });

    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

function componentToHex(c) {
    var hex = c.toString(16);
    return hex.length == 1 ? "0" + hex : hex;
}
function rgbToHex(r, g, b) {
    return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
}


function daysInMonth(month, year) {
    return new Date(year, month, 0).getDate();
}

function createDateSelector (args) {
    if(typeof args === 'undefined' || args == null)
        args = {};

    var currentYear = new Date().getFullYear();
    var minYear = args.minYear || 1969;
    var maxYear = args.maxYear || currentYear + 1;

    var $selector = ich['dateselector']();
    var $hiddenInput = $selector.find('input.date');
    var $dayInput = $selector.find('.day');
    var $monthInput = $selector.find('.month');
    var $yearInput = $selector.find('.year');

    var saveDate = function() {
        var day = $dayInput.val() || '';
        var month = $monthInput.val() || '';
        var year = $yearInput.val() || '';
        $hiddenInput.val(day + '.' + month + '.' + year);
        console.log('savedate', $hiddenInput.val());

        if(typeof args.callback !== 'undefined' &&  args.callback != null)
            args.callback()
    };

    $monthInput.add($yearInput).unbind('change').bind('change', function() {
        var days = daysInMonth($monthInput.val(), $yearInput.val());
        $.each($dayInput.find('option'), function(){
            if(this.value > days) $(this).hide();
            else $(this).show();
        });
        saveDate();
    });
    $dayInput.unbind('change').bind('change', saveDate);

    for(var i = minYear; i <= maxYear; i++) {
        $yearInput.append($('<option></option>').val(i).text(i));
    }
    return $selector;
}


/**
 * The function converts URL's GET value to JSON;
 * @param {int} string is error message;
 */
function getJsonFromUrl(string) {
//    var query = location.search.substr(1);
    var data = string.split("&");
    var result = {};
    for(var i=0; i<data.length; i++) {
        var item = data[i].split("=");
        result[item[0]] = item[1];
    }
    return result;
};


function getURLParams(url) {
    var uri = url || window.location.search;
    var params = {};
    uri.replace(
        new RegExp("([^?=&]+)(=([^&]*))?", "g"),
        function($0, $1, $2, $3) { params[$1] = $3; }
    );
    return params;
}

var modifyValue = function(value) {
    if(typeof value === 'undefined' || value == null || value == 'false' || value == '0') return 0;
    if(value == 'true' || value == '1') return 1;
    return value;
};
