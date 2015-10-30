var Themeroller = function(data) {
    var themeroller = this;

    if(typeof data === 'undefined') {
        throw 'Error. There is no data parameters.';
        return;
    }

    var $styles = $('<style>').attr('id','generator_styles').attr('type','text/css');

    if(typeof window.flexiweb !== 'undefined') {
        themeroller.siteUrl = window.flexiweb.site_url;
    }
    if(typeof FXAPI !== 'undefined') {
        themeroller.siteUrl = FXAPI.SITE_URL;
    }

    this.associations = {
        global: '',

        fx_button: ['.fx_button.exit, .fx_button.exit:hover', 'background'],

        loader: '#loader',
        first_color: ['.spinner .bounce1', 'background-color'],
        second_color: ['.spinner .bounce2', 'background-color'],
        third_color: ['.spinner .bounce3', 'background-color'],

        widget_offset: '.fx_widget:not(.inset)',
        widget_inset_font: '.fx_widget.inset',
        widget_inset_background: '.fx_widget.inset',

        button_normal: '.fx_button, tr.link td:last-child:after',
        button_hover: '.fx_button:hover, tr.link:hover td:last-child:after',
        button_pressed: '.fx_button:target, tr.link:target td:last-child:after',

        button_apply_normal: '.fx_button.fx_button_apply',
        button_apply_hover: '.fx_button.fx_button_apply:hover',
        button_apply_pressed: '.fx_button.fx_button_apply:target',

        button_danger_normal: '.fx_button.fx_button_danger',
        button_danger_hover: '.fx_button.fx_button_danger:hover',
        button_danger_pressed: '.fx_button.fx_button_danger:target',

        border_color: ['','border-color'],
        background: ['','background'],
        link_color: ['a', 'color'],
        font_color: ['','color'],

        app_background: ['.page', 'background-color'],

        font: ['body','font-family'],

        inputs_background: ['.ui-input-text','background-color'],
        background_image: ['body, #body_background','background-image'],
        background_image_scale: ['body, #body_background','background-size'],
        background_image_repeat: ['body, #body_background','background-repeat'],
        header_font_color: ['.fx_widget_title', 'color'],
        header_background: ['.fx_widget_title', 'background-color'],

        list_header_background: ['table th', 'background-color'],

        pages_switcher: ['.input_with_pages', 'background-color'],
        odd_stripes_background:
            [
                [
                    '.fx_widget_content',
                    '.fx_querylist_content:not(.stripes_vertical) tr td',
                    '.fx_querylist_content:not(.stripes_horizontal) tr td',

                    '.fx_dataform_content:not(.stripes_vertical)',
                    '.fx_dataform_content:not(.stripes_horizontal)',

                    'table tr td'
                    //'.stripes_vertical tr td:nth-child(odd)',
                    //'.stripes_horizontal tr:nth-child(odd) td',
                    //'.stripes_horizontal:not(table) > *:nth-child(odd)',
                    //'.stripes_vertical:not(table) * > *:nth-child(odd)'
                ],

                'background'
            ],
        even_stripes_background:
            [
                [
                    '.stripes_vertical tr td:nth-child(even)',
                    '.stripes_horizontal tr:nth-child(even) td',
                    '.stripes_horizontal:not(table) > *:nth-child(even)',
                    '.stripes_vertical:not(table) * > *:nth-child(even)'
                ],

                'background'
            ],
        box_shadow_color: ['.fx_widget','box-shadow'],
        box_shadow_size: ['.fx_widget','box-shadow'],
        border_radius:
            [
                [
                    '.fx_widget.inset',
                    '.fx_widget.inset .fx_widget_content'
                ],
                'border-radius'
            ],
        top_navigation: ['.fx_top_navigation, .fx_bottom_navigation', 'background-color']


    };
    this.getDefaultStyles = function() {
        return {
            'global' : {
                'font' :  'Helvetica',
                //        'active_state_background' :  '#404040',
                //        'active_state_color' :  '#404040',
                'border_radius' :  '5',
                'top_navigation' :  '#404040',
                'app_background' :  '#404040',

                'box_shadow_color' :  '#404040',
                'box_shadow_size' :  ''
            },
            'loader' : {
                'first_color': '#41ABDC',
                'second_color':'#EE3834',
                'third_color': '#A4CD39'
            },

            'widget_offset' : {
                'header_font_color' :  '#404040',
                'font_color' :  '#444444',
                'link_color' :  '#1fa3ec'
            },
            'widget_inset_font' : {
                'header_font_color' :  '#FFFFFF',
                'font_color' :  '#404040',
                'link_color' :  '#1fa3ec'
            },
            'widget_inset_background' : {
                'header_background' :  '#404040',
                'background' :  '#c2baba',
                'list_header_background' :  '#efefef',
                'pages_switcher' :  '#fefefe',
                'odd_stripes_background' :  '#95bcc9',
                'even_stripes_background' :  '#ffe8e8',
                'border_color' :  '#404040'
            },


            //      buttons
            'button_normal' : {
                'font_color' :  '#444444',
                'background' :  '#DADADA',
                'border_color' :  '#444'
            },
            'button_hover' : {
                'font_color' :  '#444444',
                'background' :  '#A7A7A7',
                'border_color' :  '#444'
            },
            'button_pressed' : {
                'font_color' :  '#444444',
                'background' :  '#A7A7A7',
                'border_color' :  '#444'
            },

            //      apply button
            'button_apply_normal' : {
                'font_color' :  '#FFFFFF',
                'background' :  '#168FEC',
                'border_color' :  '#168FEC'
            },
            'button_apply_hover' : {
                'font_color' :  '#FFFFFF',
                'background' :  '#196EAF',
                'border_color' :  '#168FEC'
            },
            'button_apply_pressed' : {
                'font_color' :  '#FFFFFF',
                'background' :  '#168FEC',
                'border_color' :  '#168FEC'
            },


            //      danger button
            'button_danger_normal' : {
                'font_color' :  '#FFFFFF',
                'background' :  '#E97272',
                'border_color' :  '#E97272'
            },
            'button_danger_hover' : {
                'font_color' :  '#FFFFFF',
                'background' :  '#BB5959',
                'border_color' :  '#BB5959'
            },
            'button_danger_pressed' : {
                'font_color' :  '#FFFFFF',
                'background' :  '#BB5959',
                'border_color' :  '#BB5959'
            }

        };
    };
    this.makeSelector = function(selectors, parent_block) {
        var selector = '';
        if($.isArray(selectors))  {
            $.each(selectors, function(key, value) {
                if(key != 0) selector += ', ';
                selector += (value == '')? parent_block : ((parent_block == '')? '' : parent_block + ' ') +  value;
            })
        }
        else {
            selector += (selectors == '')? parent_block : ((parent_block == '')? '' : parent_block + ' ') +  selectors;
        }
        return selector;
    };
    this.previewRedraw = function(inputName, inputValue) {
        var results = inputName.split('.');
        $('[name="' + inputName + '"]').val(inputValue);

        var secondParam = ((results[0] == 'global')? '' : themeroller.associations[results[0]]),
            item = results[1],
            selector = themeroller.makeSelector(themeroller.associations[item][0], secondParam),
            property = themeroller.associations[item][1];

        switch(item) {
            case 'font_color': property = 'color'; break;

            case 'background_color':
            case 'first_color':
            case 'second_color':
            case 'third_color':
                property = 'background';
                break;

            case 'border_color':  property = 'border-color'; break;

            case 'background_image':
                inputValue = inputValue == 'none' || !inputValue ? 'none' : "url('" + inputValue + "'); ";
                break;
            case 'fx_button': {
                inputValue = "";
                break;
            }

            case 'box_shadow_size':
                inputValue = "0 1px " + inputValue + " " + $('input[name="global.box_shadow_color"]').val();
                break;

            case 'box_shadow_color':
                inputValue = "0 1px " + $('input[name="global.box_shadow_color"]').val()  + " "+ inputValue;
                break;

            case 'border_radius' :
                inputValue += 'px';
                break;

            case 'font':
                property = 'font-family';
                inputValue = (inputValue == 'font' || inputValue == 'font2') ? 'sans-serif' : inputValue;
                break;

        }

        var currentStyle = selector + " { "+ property +": "+inputValue  +" }\n";

        //if(typeof $styles !== 'undefined') {}

        $styles.append(currentStyle);
        $("iframe").contents().find('#generator_styles').remove();
        $("iframe").contents().find('head').prepend($styles);

        return currentStyle;

    };
    this.createStyles = function() {
        var styles = [];

        var restructuredStyles = {};
        for( var group in stylesList) {
            for (var item in stylesList[group]) {
                restructuredStyles[group + '.' + item] = stylesList[group][item];
            }
        }

        for (var item in restructuredStyles ) {
            if(restructuredStyles.hasOwnProperty(item)) {
                var tt = themeroller.previewRedraw(item, restructuredStyles[item]);
                styles.push(tt);
            }
        }

        return (function(res) { return res; })(styles)
    };

    var stylesList;

    if(!data.style)
        stylesList = themeroller.getDefaultStyles();

    else {
        try {
            stylesList = JSON.parse(data.style);
        }
        catch(e) {
            stylesList = data.style;
        }
    }
};