var genericApplication = function(app) {
    /**
     * Try to init application with @app.
     */

    try {
        app = JSON.parse(app);
    }
    catch(e) {
        app = undefined;
    }

    var isSpecificApp = !!app;
    var genericApp = this;

    var $genericApp = genericApp.$genericApp = $('#generic_application');
    var $switchers = $('.network_mode_switcher');

    this.isSpecificApp = isSpecificApp;

    /**
     * @isKeyboardShows {boolean} sets true if keyboard is visible and false if isn't.
     */
    this.isKeyboardShows = false;

    this.loaderIndicator = 0;

    this.pushesIncluded = false;

    /**
     * This object returns device's OS version.
     */
    this.isMobile = {
        Android: function() {
            return navigator.userAgent.match(/Android/i);
        },
        BlackBerry: function() {
            return navigator.userAgent.match(/BlackBerry/i);
        },
        iOS: function() {
            return navigator.userAgent.match(/iPhone|iPad|iPod/i);
        },
        Opera: function() {
            return navigator.userAgent.match(/Opera Mini/i);
        },
        Windows: function() {
            return navigator.userAgent.match(/IEMobile/i);
        },
        any: function() {
            var isMobile = genericApp.isMobile;
            return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
        }

    };

    this.isPhonegap = function() {
        return (typeof(cordova) !== 'undefined' || typeof(phonegap) !== 'undefined');
    };

    this.checkConnection = function() {
        return (genericApp.isPhonegap()) ? navigator.onLine : true;
    };

    this.initPushwoosh = function() {
        if(genericApp.isPhonegap()) {
            switch(device.platform) {
                case 'Android': genericApp.registerPushwooshAndroid(); break;
                case 'iOS': case 'iPhone': genericApp.registerPushwooshIOS(); break;
                case 'Win32NT': genericApp.registerPushwooshWP(); break;
                default: break;
            }
        }
    };

    /**
     * This method creates specify (single) application without generic app.
     */
    this.createSpecificApp = function() {
        genericApp.$genericApp = $();
        new Application(app, genericApp);
    };

    /**
     * This method sets new sizes of application page in GENERIC && CURRENT applications.
     */
    this.recalculateSizes = function() {
        //cordovaAlert('recalculate');
        var orientation = genericApp.getOrientation();
        genericApp.recalculateApplicationSizes(orientation);
        genericApp.recalculateGenericApplicationSizes(orientation);
    };

    /**
     * This method returns device orientation.
     */
    this.getOrientation = function() {
        var orientation = 'portrait',
            $body = $('body'),
            screenHeight = $body.height(),
            screenWidth = $body.width();

        if(window.orientation === 90 || window.orientation === -90)  orientation = 'landscape';

        if(!this.isMobile.any()) {
            orientation = (screenWidth > screenHeight)? 'landscape' : 'portrait';
        }

        return orientation;
    };

    this.getSize = function(side) {
        var orientation = genericApp.getOrientation();
        var $body = $('body');
        var screenHeight = $body.height();
        var screenWidth = $body.width();
        var realWidth;
        var realHeight;
        if(genericApp.isMobile.Android()) {
            if(orientation == 'portrait') {
                realHeight = (screenHeight < screenWidth) ? screenWidth : screenHeight;
                realWidth = (screenHeight > screenWidth) ? screenWidth : screenHeight
                ////console.log('getSize :: ANDROID :: PORTRAIT', realHeight, realWidth)
            }
            else {
                realHeight = (screenHeight > screenWidth) ? screenWidth : screenHeight;
                realWidth = (screenHeight < screenWidth) ? screenWidth : screenHeight
                ////console.log('getSize :: ANDROID :: LANDSCAPE', realHeight, realWidth)

            }
        }
        else {
            realHeight = screenHeight;
            realWidth = screenWidth;
            ////console.log('getSize :: NOT ANDROID ::', realHeight, realWidth)
        }

        return (side == 'height') ? realHeight : realWidth;

    };
    this.getWidth = function() {
        return this.getSize('width');
    };

    this.getHeight = function() {
        return this.getSize('height');
    };

    this.getMinimalSide = function() {
        return Math.min(genericApp.getSize('height'), genericApp.getSize('width'));
    };

    /**
     * This method sets new size of application page in CURRENT application
     */
    this.recalculateApplicationSizes = function(orientation) {
        var $parentDiv = $('#application');
        var $navigationTop = $parentDiv.find(".fx_top_navigation");
        var $bottomNavigation = $parentDiv.find(".fx_bottom_navigation");
        var isThereBottomNavigation = ($bottomNavigation.length > 0);
        var leftMargin = $navigationTop.find('.buttons').outerWidth();
        $navigationTop.find(".with_set_selector").css('margin-left', leftMargin + 'px');

        var topNavigationHeight = $navigationTop.outerHeight();
        var css = {'padding-top' : topNavigationHeight + 'px'};//,'padding-bottom' : topNavigationHeight + 'px' };
        var specificApp = this.application;

        //if(typeof specificApp !== 'undefined') {
        //    if(typeof specificApp.pages !== 'undefined') {
        //        var currentPage = specificApp.pages[specificApp.activePageId];
        //        if (typeof currentPage.iScroll !== 'undefined') {
        //            console.log('q');
        //            currentPage.iScroll.scrollTo(0, 0);
        //        }
        //    }
        //}

        if(!isThereBottomNavigation) {
            css['padding-bottom'] = 0
        }
        else if(orientation == 'landscape') {
            css['padding-left'] = '17%';
            css['padding-bottom'] = 0;
            $bottomNavigation.height($('#generic_body_background').height() - topNavigationHeight)
        }
        else if(orientation == 'portrait'){
            css['padding-bottom'] = $bottomNavigation.outerHeight() + 'px';
            css['padding-left'] = '';
            $bottomNavigation.height('');
        }

        $parentDiv.find('.page_content').css('min-height', $('#generic_body_background').height());

        var $typicalPages = $parentDiv.find('.page').not('[data-page-id="startPage"]').filter('[data-page-id]');
        $typicalPages.find('.page_content').css(css);
    };

    /**
     * This method sets ew size of application page in GENERIC application
     */
    this.recalculateGenericApplicationSizes = function(orientation) {
        var $body = $('body');
        var appsListHeight;
        var $styles = $('#forDifferentDimensions');
        var height =  genericApp.getHeight();
        var sideSize = (genericApp.isMobile.Android()) ? height * 0.85 : height * 0.94;

        appsListHeight = sideSize - $body.find('.header').outerHeight();

        if(orientation == 'portrait')
            appsListHeight = appsListHeight - ($body.find('.footer').outerHeight() + 16);

        appsListHeight = Math.floor(appsListHeight);

        if($styles.length == 0)
            $styles = $('<style/>').attr('id', 'forDifferentDimensions');

        $styles.empty()
            .append('.page_with_list { height: '+ appsListHeight + 'px; }')
            .append('#page_search .page_with_list { height: ' + (appsListHeight - 58) + 'px; }');

        $body.append($styles);
    };

    /**
     * This method inits network switcher in app's header.
     */
    this.networkStateChange = function() {
        console.log('network state change');
        var mode = FXAPI.offlineMode;

        if(!genericApp.isPhonegap()) {
            genericApp.cordovaAlert('Offline mode is not available in preview.');
            return;
        }

        if(mode && !genericApp.checkConnection()) {
            genericApp.cordovaAlert('You have no connection. Try again later.');
            return;
        }

        if(mode) $switchers.removeClass('offline');
        else $switchers.addClass('offline');
        FXAPI.offlineMode = !mode;
        genericApp.refreshPage();
        //genericApp.$genericApp.find('.button.download').prop('disabled', !mode);

    };
    this.setOfflineMode = function(value) {
        if(!genericApp.isPhonegap()) return;
        if(FXAPI.offlineMode == value) return;
        $switchers.first().trigger('click');
    };

    /**
     * This method shows generic application.
     */
    this.start = function() {
        $genericApp.show();
        $genericApp.find('#home_button').trigger('click');
        genericApp.initProfilePage();
        genericApp.recalculateSizes();
    };

    /**
     * This method inits generic application.
     */
    this.init = function() {
        $.extend(FXAPI, {
            BASE_API_URL: FXAPI.SITE_URL + "api/v1/",
            UPLOADS_URL: FXAPI.SITE_URL + "uploads/",
            createTableTransaction: genericApp.createTableTransaction,
            dropTableTransaction: genericApp.dropTableTransaction,
            fillTableTransaction: genericApp.fillTableTransaction,
            isPhonegap: genericApp.isPhonegap,
            cordovaAlert: genericApp.cordovaAlert
        });
        FXAPI.offlineMode = DFXAPI.offlineMode = false;

        var $login = $('#login');
        var $loginButton = $login.find('#login_button');
        var initApplication = isSpecificApp ? genericApp.createSpecificApp : genericApp.start;
        var localUsing = isSpecificApp ? app.is_local_using || app.is_local_using == 'true' : false;

        $loginButton.unbind('click').bind('click', function(){
            $loginButton.prop('disabled', true);
            var email = $login.find('input[name=user]').val();

            var argsForLogin = {
                'email' : email,
                'password' :  $login.find('input[name=password]').val(),
                'channel_id' : isSpecificApp ? app.channel_id : undefined
            };

            FXAPI.tryLoginUser(argsForLogin, function(loginData){
                if(!loginData.api_key) return;

                FXAPI.GLOBAL_API_KEY = localStorage.api_key = loginData.api_key;
                FXAPI.USER_ID = localStorage.user_id = loginData.user_id;
                FXAPI.currentUserMail = localStorage.currentUserMail = email;
                FXAPI.userData = loginData;

                if(!$login.find('input[name=remember]').is(':checked'))
                    localStorage.clear();

                $login.hide();
                $loginButton.prop('disabled', false);
                initApplication();

            }, function(errors) {
                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                $loginButton.prop('disabled', false);
            });
        });

        if(genericApp.pushesIncluded)
            genericApp.initPushwoosh();

        genericApp.initDatabase();
        genericApp.initControls();

        if(!genericApp.checkConnection())
            genericApp.setOfflineMode(true);

        $genericApp.hide();
        $login.hide();

        if(localUsing) {
            initApplication();
        }
        else if(localStorage.api_key) {
            FXAPI.GLOBAL_API_KEY = localStorage.api_key;
            FXAPI.USER_ID = localStorage.user_id;
            FXAPI.currentUserMail = localStorage.currentUserMail;
            initApplication();
        }
        else {
            $login.show();
        }
    };

    /**
     * This method fills template, create event listeners.
     */
    this.initControls = function() {
        var $genericApp = genericApp.$genericApp;
        var $goHomePageButton = $genericApp.find('#home_button');
        var $goToSearchPageButton = $genericApp.find('#search_button');
        var $goToProfilePageButton = $genericApp.find('#profile_button');
        var $searchPage = $genericApp.find('#page_search');
        var $startSearchButton = $searchPage.find('#start_search');
        var $goToInfoPageButton = $genericApp.find('#user_apps_info_button');
        var $portals = $genericApp.find('#portals');
        var $notificationsButton = $genericApp.find('.notifications');

        $portals.unbind('change').bind('change', function() {
            $startSearchButton.trigger('click');
        });
        $startSearchButton.unbind('click').bind('click', function(){
            var $searchString = $genericApp.find('#search_string');
            $searchString.find('.apps_list').empty();

            var offsetVal = undefined, limit = 30;
            var timeout = genericApp.loaderShow();

            var createAppsList = function(apps) {
                genericApp.fillContent(apps, $searchPage, function(data){
                    //create page
                    var timeout = genericApp.loaderShow();
                    FXAPI.getAppObject(data.app_id, function(fullAppData){
                        genericApp.fillApplicationDescription(fullAppData);
                        genericApp.goToPage('#page_description');
                        genericApp.loaderHide(timeout);
                        //genericApp.ifThereIsNoErrors(fullAppData, function(){ });
                    }, function(errors) {
                        genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                        genericApp.loaderHide(timeout);
                    });
                    $goToSearchPageButton.prop('disabled', true);
                });
                genericApp.initNavigation($searchPage);
                genericApp.loaderHide(timeout);
            };

            FXAPI.getApplications($portals.val(), $searchString.val(), offsetVal, limit, createAppsList, function(errors) {
                createAppsList([]);
                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
            });
        });

        $goToSearchPageButton.unbind('click').bind('click', function(){
            $goToSearchPageButton.prop('disabled', true);
            var timeout = genericApp.loaderShow();

            genericApp.goToPage($searchPage);

            FXAPI.getPortals(function(portals){
                var t = false;
                $portals.empty().append($('<option>').val(0).text('Not selected'));

                $.each(portals, function(role, list) {
                    t = true;
                    $.each(list, function(id, data) {
                        $portals.append($('<option>').val(id).text(data.display_name));
                    });
                });

                if(t) $portals.show();
                $startSearchButton.trigger('click');
                genericApp.loaderHide(timeout);

            }, function(errors) {
                $portals.hide();
                genericApp.loaderHide(timeout);
                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
            });
        });
        $goToProfilePageButton.unbind('click').bind('click', function(){
            genericApp.goToPage('#page_profile');
        });
        $goToInfoPageButton.unbind('click').bind('click', function(){
            var timeout = genericApp.loaderShow();

            var $infoPage = $genericApp.find('#page_user_apps_info');
            $infoPage.find('.apps_list').empty();

            genericApp.goToPage($infoPage);

            $goToInfoPageButton.prop('disabled', true);

            var createAppsList = function(apps) {
                if(!isEmpty(apps)) {
                    var appsGroupedById = {};
                    var appsWithoutDuplicates = [];

                    $.each(apps, function(i, appData) {
                        appsGroupedById[appData.app_id] = appData;
                    });

                    $.each(appsGroupedById, function(i, data) {
                        appsWithoutDuplicates.push(data);
                    });

                    genericApp.fillContent(appsWithoutDuplicates, $infoPage, function(data){
                        var timeout = genericApp.loaderShow();
                        FXAPI.getAppObject(data.app_id, function(fullAppData){
                            fullAppData.previousPage = $infoPage.attr('id');
                            genericApp.fillApplicationDescription(fullAppData);
                            genericApp.goToPage('#page_description');
                            genericApp.loaderHide(timeout);

                        }, function(errors) {
                            genericApp.loaderHide(timeout);
                            genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                        });

                    }, 'list');
                }

                genericApp.initNavigation($infoPage);
                $goToInfoPageButton.prop('disabled', false);
                genericApp.loaderHide(timeout);
            };

            FXAPI.getInstalledApplications(createAppsList, function(errors) {
                createAppsList([]);
                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
            });
        });
        $goHomePageButton.unbind('click').bind('click', function(){
            var timeout = genericApp.loaderShow();
            var $mainPage = $genericApp.find('#page_user_apps');

            $mainPage.find('.apps_list').empty();
            genericApp.goToPage($mainPage);
            $goHomePageButton.prop('disabled', true);

            var createAppsList = function(apps) {
                if(!isEmpty(apps)) {
                    genericApp.fillContent(apps, $mainPage, function(currentAppData){
                        new Application(currentAppData, genericApp);
                    });
                }
                genericApp.initNavigation($mainPage);
                $goHomePageButton.prop('disabled', false);
                genericApp.loaderHide(timeout);
            };

            FXAPI.getInstalledApplications(createAppsList, function(errors) {
                createAppsList([]);
                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));

            });
        });//.trigger('click');
        $notificationsButton.unbind('click').bind('click', function(){
            genericApp.goToPage('#page_notifications');

            if(!genericApp.isPhonegap())
                genericApp.actionOnPush({text: 'It wsadefwfgew fejwfej fn  few f yt iiig ervcv bvc bvb v bvbbbbb', type: 'request'});
                genericApp.actionOnPush({text: 'It wsadefwfgew fejwfej fn  few f yt iiig ervcv bvc bvb v bvbbbbb', type: 'update'});
                genericApp.actionOnPush({text: 'It wsadefwfgew fejwfej fn  few f yt iiig ervcv bvc bvb v bvbbbbb', type: 'notification'});
                genericApp.actionOnPush({text: 'It wsadefwfgew fejwfej fn  few f yt iiig ervcv bvc bvb v bvbbbbb', type: 'calendar'});
        });

        $switchers.unbind('click').bind('click', genericApp.networkStateChange);
    };

    /**
     * This method fills main & search pages by applications.
     * @data is object contains apps' data { app_id : app_data }
     * @$currentPage is link to page object
     * @actionOnClick is callback by click on list item;
     * @typeOfView is type of view: flat or grid
     */
    this.fillContent = function(data, $currentPage, actionOnClick, typeOfView) {
        var currentPageId = $currentPage.attr('id');
        var isHomePage = currentPageId == 'page_user_apps';
        //var isListView = (typeOfView == 'list' || 'notifications_list' );
        var isListView = !!typeOfView;

        var createIconForGridLayout = function(itemData) {
            var visibleLabel = itemData.title;
            if(isHomePage)
                visibleLabel += ' [' + itemData.version + ']';

            itemData.visible_label = visibleLabel;

            var $app = ich['iconForGridLayout'](itemData);
            $app.find('.icon').css('background-image', 'url(' + itemData.icon_large + ')');
            $app.unbind('click').bind('click', function(){
                $(this).prop('disabled', true);
                actionOnClick(itemData);
                $(this).prop('disabled', false);
            });
            return $app;
        };
        var createIconForListLayout = function(itemData) {
            var $app = ich['iconForListLayout'](itemData);

            $app.find('.button.blue').unbind('click').bind('click', function(){
                $(this).prop('disabled', true);
                actionOnClick(itemData);
                $(this).prop('disabled', false);
            });

            $app.find('.button.green').unbind('click').bind('click', function(){
                $(this).prop('disabled', true);

                var application = itemData;

                $.extend(DFXAPI, {
                    SITE_URL: application.base_url,
                    BASE_API_URL: application.base_api_url,
                    API_KEY: application.channel_key,
                    SCHEMA_ID: application.remote_schema_id || application.schema_id,
                    UPLOADS_URL: application.base_url + "uploads/",
                    encryptKey: application.channel_token,
                    db: genericApp.db
                });

                DFXAPI.getUserSets(function(sets) {
                    DFXAPI.dataSets = sets;
                    genericApp.fillDataSetsPage(itemData, sets);
                    genericApp.goToPage('#page_datasets_list');
                    $(this).prop('disabled', false);
                });
            });

            if(genericApp.isPhonegap()) {
                genericApp.db.transaction(function(tx) {
                    tx.executeSql('SELECT * FROM apps_tbl WHERE app_id =' + itemData.app_id, [], function(tx, res) {
                        $app.find('.download_date').append('Downloaded: ' + genericApp.createSignature(res.rows.item(0).synchronisation_date));
                    });
                }, $.noop)
            }
            else {
                $app.find('.download_date').append('Downloaded: unknown.');
            }

            return $app;

        };
        var createIconForNotification = function(itemData) {

            var createRequest = function(itemData) {
                return ich['notification_request'](itemData)
            };
            var createNotification = function(itemData) {
                return ich['notification_info'](itemData);
            };
            var createCalendar = function(itemData) {
                return ich['notification_calendar'](itemData);

            };
            var createUpdate = function(itemData) {
                return ich['notification_update'](itemData)
            };

            switch(itemData.type) {
                case 'request': return createRequest(itemData); break;
                case 'calendar': return createCalendar(itemData); break;
                case 'update': return createUpdate(itemData); break;
                case 'notification': default:  return createNotification(itemData); break;
            }
        };

        var createItemsList = function(itemsPerPage) {
            // get new data model for apps
            var itemsList = [],
                $appsList = $();

            $.each(data, function(id, currentItemData){
                itemsList.push(currentItemData);
            });


            for (var pageNumber = 0; pageNumber < Math.ceil(itemsList.length / itemsPerPage); pageNumber++) {
                var $page = $('<div>').addClass('page_with_list').attr('data-page', pageNumber);

                if(isListView)
                    $page.append($('<table></table>').css('height', '100%'));

                //if(pageNumber == 0)
                //    $page.addClass('active');

                for (var item = 0; item < itemsPerPage; item++) {
                    var appData = itemsList[pageNumber * itemsPerPage + item];

                    if(appData) {
                        var $itemIcon;

                        if(isListView) {
                            $itemIcon = typeOfView == 'list' ? createIconForListLayout(appData) : createIconForNotification(appData);
                            $page.find('table').append($itemIcon);
                        }
                        else {
                            $itemIcon = createIconForGridLayout(appData);
                            $page.append($itemIcon);
                        }
                    }
                    else if(isListView) {
                        $page.find('table').append($('<tr class="app_list_view empty"/>').append('<td colspan="5">'));
                    }
                }

                $appsList = $appsList.add($page);
            }
            return $appsList;
        };

        var itemsPerPage = (isListView) ? 6 : 12;
        var swipeName = 'swipe_' + currentPageId;


        if(!!window[swipeName]) {
            window[swipeName].kill();
        }

        $currentPage.find('.apps_list').empty().append(createItemsList(itemsPerPage));


        window[swipeName] = Swipe($currentPage.find('.swipe').get(0),{
            startSlide: 0,
            speed: 300,
            continuous: false,
            disableScroll: false,
            stopPropagation: false,
            callback: function(index, elem) {
                var $items = $currentPage.find('.navigation_item');
                $items.removeClass('active');
                $items.filter('[data-page="' + index + '"]').addClass('active');
            },
            transitionEnd: function(index, elem) {}
        });

        window[swipeName].slide(0);//, 300);
    };

    /**
     * This method switches page to another.
     */
    this.goToPage = function(page) {
        //var timeout = genericApp.loaderShow();
        // page can be jquery object or string with id
        //var $genericApp = genericApp.$genericApp;
        var $page = $(page);

        $genericApp.find('.generic_app_page.active').find('.rating').removeClass('loaded');
        $genericApp.find('.generic_app_page').removeClass('active');

        switch($page.attr('id')) {
            case 'page_user_apps' :
                $genericApp.find('.footer .app_menu_item').removeClass('active');
                $('#home_button').addClass('active');
                break;
            case 'page_user_apps_info' :
                $genericApp.find('.footer .app_menu_item').removeClass('active');
                $('#user_apps_info_button').addClass('active');
                break;
            case "page_search" :
                $genericApp.find('.footer .app_menu_item').removeClass('active');
                $('#search_button').addClass('active');
                break;
            case 'page_profile' :
                $genericApp.find('.footer .app_menu_item').removeClass('active');
                $('#profile_button').addClass('active');
                break;
            //default:
            //    genericApp.loaderHide(timeout);
        }

        $page.addClass('active').show();

        if($page.hasClass('withoutFooter')) $genericApp.find('.footer').hide();
        else $genericApp.find('.footer').show();

        $page.find('.rating').focus().addClass('loaded');
    };

    this.refreshPage = function() {
        //var timeout;
        //if(FXAPI.offlineMode)
        //var timeout = genericApp.loaderShow();
        switch(genericApp.$genericApp.find('.generic_app_page.active').attr('id')) {
            case 'page_user_apps': $('#home_button').trigger('click'); break;
            case 'page_user_apps_info': $('#user_apps_info_button').trigger('click'); break;
            case "page_search": $('#search_button').trigger('click'); break;
            //case 'page_profile': $('#profile_button').trigger('click'); break;
            default: //genericApp.loaderHide(timeout);
        }
    };

    /**
     * This method creates user profile page.
     */
    this.initProfilePage = function() {
        var dateFormat = 'dd.MM.yyyy';
        var $profilePage = genericApp.$genericApp.find('#page_profile');
        var initData = function(userData, type) {
            $profilePage.empty().append(ich['page_profile'](userData));
            var isEdit = (type == 'edit');
            var $userData = $profilePage.find('.user_data');
            var requiredFields = ['display_name', 'dob', 'first_name', 'last_name', 'phone_number', 'password', 'new_password'];
            $.each(requiredFields, function(i, fieldName) {
                var $dataRow = $();
                var isFieldNameIsPwd = ((fieldName == 'new_password') || (fieldName == 'password'));
                var currentOptionRow = {
                    property_name: fieldName,
                    property_value: userData[fieldName]
                };

                if(fieldName == 'dob') {
                    var date = new Date(userData[fieldName] * 1000);
                    currentOptionRow.property_value = date.toString(dateFormat);
                }

                if(isFieldNameIsPwd) {
                    if(isEdit) {
                        currentOptionRow.property_value = '';
                        $dataRow = ich['property_edit'](currentOptionRow);
                        $dataRow.find('input').attr('type', 'password');
                    }
                }
                else {
                    $dataRow = isEdit ? ich['property_edit'](currentOptionRow) : ich['property_read_only'](currentOptionRow);
                }
                $userData.append($dataRow);
            });

            if(isEdit) {
                $profilePage.find('img').hide();
                $profilePage.find('.title').hide();
                genericApp.$genericApp.find('.footer').hide();
            }
            else {
                genericApp.$genericApp.find('.footer').show();
            }


        }
        var initButtons = function(userData) {
            var $logoutButton = $profilePage.find('.button.logout'),
                $editProfileButton = $profilePage.find('.button.edit'),
                $removeLocalData = $profilePage.find('.button.removeLocalData');

            $logoutButton.unbind('click').bind('click', function(){
                $logoutButton.prop('disabled', true);
                localStorage.clear();
                genericApp.init();
                $logoutButton.prop('disabled', false);
            });
            $editProfileButton.unbind('click').bind('click', function(){
                $editProfileButton.prop('disabled', true);

                var $saveChangesButton = $('<div/>').addClass('button green').text('Save');
                var $discardChangesButton = $('<div/>').addClass('button red').text('Cancel');

                initData(userData, 'edit');
                initButtons(userData);

                $saveChangesButton.unbind('click').bind('click', function(){
                    $saveChangesButton.prop('disabled', true);

                    var newUserData = {};
                    $.each($profilePage.find('.user_data .property_value'), function(index, item){
                        var $item = $(item);
                        var name = $item.attr('name');

                        if(name == 'dob') {
                            var date = new Date.parseExact($item.val(),  dateFormat);
                            newUserData[name] = date.getTime() / 1000;
                        }
                        else {
                            newUserData[name] = $item.val();
                        }
                    });

                    if(newUserData.password == '') {
                        delete newUserData.old_password;
                        delete newUserData.password;
                    }


                    var timeout = genericApp.loaderShow();
                    FXAPI.saveUserData(newUserData, function(response){
                        if(response) {
                            genericApp.cordovaAlert('User info was updated!');
                            initData(newUserData);
                            initButtons(newUserData);
                            userData = newUserData;
                        }
                        $saveChangesButton.prop('disabled', false);
                        genericApp.loaderHide(timeout);
                    }, function(errors){
                        initData(userData);
                        initButtons(userData);
                        $saveChangesButton.prop('disabled', false);
                        genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                        genericApp.loaderHide(timeout);
                    })
                });
                $discardChangesButton.unbind('click').bind('click', function(){
                    $discardChangesButton.prop('disabled', true);
                    initData(userData);
                    initButtons(userData);
                    $discardChangesButton.prop('disabled', false);
                });

                $profilePage.find('.button.logout').hide();
                $profilePage.find('.button.edit').hide();
                $profilePage.append($saveChangesButton).append($discardChangesButton);
                $editProfileButton.prop('disabled', false);

            });
            $removeLocalData.unbind('click').bind('click', function(){
                if($removeLocalData.attr('disabled')) return;

                genericApp.cordovaConfirm('Are you sure to remove all local data?', function(){
                    genericApp.dropTableTransaction('apps_tbl');
                    genericApp.dropTableTransaction('users_tbl');
                    genericApp.dropTableTransaction('user_apps_tbl');
                    genericApp.dropTableTransaction('user_sets_tbl');

                    genericApp.dropTableTransaction('enum_type_tbl');
                    genericApp.dropTableTransaction('enum_field_tbl');
                    genericApp.dropTableTransaction('link_type_tbl');
                    genericApp.dropTableTransaction('link_tbl');

                    genericApp.dropTableTransaction('form_tbl');
                    genericApp.dropTableTransaction('query_tbl');
                    genericApp.dropTableTransaction('object_type_tbl');

                    genericApp.printDatabase();

                    $logoutButton.trigger('click');
                });
            });

            if(!genericApp.isPhonegap())
                $removeLocalData.attr('disabled', 1)

        };

        FXAPI.getUserData(function(data){
            initData(data);
            initButtons(data);
            //genericApp.ifThereIsNoErrors(data, function() { });
        }, function(errors) {
            genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
        });
    };

    /**
     * This method creates navigation buttons ( for swipe ).
     */
    this.initNavigation = function ($currentPage) {
        var $pages = $currentPage.find('.page_with_list'),
            $navigationDiv = $currentPage.find('.navigation_block').empty(),
            numberOfPages = $pages.length;

        if(numberOfPages > 1) {
            $.each($pages, function(i, page){
                var $currentAppsPage = $(page),
                    currentPageNumber = $currentAppsPage.attr('data-page'),
                    $navigationPageItem = $('<span>').addClass('navigation_item').attr('data-page', currentPageNumber);

                if(currentPageNumber == 0)
                    $navigationPageItem.addClass('active');

                $navigationPageItem.unbind('click').bind('click', function(){
                    var $clickedNavigationItem = $(this);
                    var currentPageNumber = $clickedNavigationItem.attr('data-page');

                    // hide old page and show new
                    //$pages.removeClass('active');
                    //$pages.filter('[data-page="'+ currentPageNumber +'"]').addClass('active');

                    // the same for navigation items
                    $('.navigation_item').removeClass('active');
                    $clickedNavigationItem.addClass('active');

                    window['swipe_' + $currentPage.attr('id')].slide(currentPageNumber);//, 300);
                });
                $navigationDiv.append($navigationPageItem);
            });
            $navigationDiv.show();
        }
        else
            $navigationDiv.hide();

    };

    /**
     * This method creates page with info about current application.
     */
    this.fillApplicationDescription = function(appData) {
        var $genericApp = genericApp.$genericApp,
            appID = appData.app_id,

            $applicationDescriptionPage = $('#page_description').empty().append(ich['page_application_info'](appData)),
            //$applicationDescriptionPage = $genericApp.find('#page_description');//.empty().append(ich['page_application_info'](appData)),

            $versionsList = $applicationDescriptionPage.find('.versionsList'),
            $backButton = $applicationDescriptionPage.find('.button.back'),
            $ratingByMarks = $applicationDescriptionPage.find('.app_number_each_mark'),
            percents = 0, max = 0, isMaxValueKnown = false,
            rating = appData.rating,
            liveVersionId = appData.live_version,
            metaData = appData.meta || {},
            marks = appData.rating_by_marks || {};

        FXAPI.BASE_API_URL = appData.base_api_url;
        $backButton.unbind('click').bind('click', function(){
            var $previousPage = $('#' + appData.previousPage);
            if($previousPage.length != 0) {
                genericApp.goToPage($previousPage);
            }
            else {
                var $searchPage = $genericApp.find('#page_search');
                genericApp.goToPage($searchPage);
                $searchPage.find('#start_search').trigger('click');
            }
        });

        try {
            if (appData.is_subscribed) {
                if (appData.versions) {
                    $.each(appData.versions, function (versionId, versionData) {
                        var $buttonsBlock = ich['buttons_block'](versionData),
                            $installButton = $buttonsBlock.find('.button.install'),
                            $startButton = $buttonsBlock.find('.button.start'),
                            $uninstallButton = $buttonsBlock.find('.button.uninstall');

                        $startButton.unbind('click').bind('click', function () {
                            $startButton.prop('disabled', true);
                            var timeout = genericApp.loaderShow();

                            var doubleId = appID + '.' + versionId;
                            FXAPI.getVersionData(doubleId, function (versionData) {
                                new Application(versionData, genericApp);
                                $startButton.prop('disabled', false);
                                genericApp.loaderHide(timeout);
                            }, function(errors) {
                                $startButton.prop('disabled', false);
                                genericApp.loaderHide(timeout);
                                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                            })
                        });
                        $installButton.unbind('click').bind('click', function () {
                            $installButton.prop('disabled', true);

                            var doubleId = appID + '.' + versionId;
                            var timeout = genericApp.loaderShow();
                            FXAPI.installApplication(doubleId, function (response) {
                                if(response) {
                                    $installButton.hide();
                                    $startButton.add($uninstallButton).show();
                                }
                                $installButton.prop('disabled', false);
                                genericApp.loaderHide(timeout);
                            }, function(errors) {
                                $installButton.prop('disabled', false);
                                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                                genericApp.loaderHide(timeout);
                            });
                        });
                        $uninstallButton.unbind('click').bind('click', function () {
                            $uninstallButton.prop('disabled', true);
                            var doubleId = appID + '.' + versionId;
                            var timeout = genericApp.loaderShow();

                            FXAPI.uninstallApplication(doubleId, function (response) {

                                if(response) {
                                    $installButton.show();
                                    $startButton.add($uninstallButton).hide();
                                }
                                //genericApp.ifThereIsNoErrors(response, function () {});
                                genericApp.loaderHide(timeout);

                                $uninstallButton.prop('disabled', false);
                            }, function(errors) {
                                $uninstallButton.prop('disabled', false);
                                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                                genericApp.loaderHide(timeout);
                            });

                        });

                        if (versionData.installed) $installButton.hide();
                        else $startButton.add($uninstallButton).hide();

                        $versionsList.append($buttonsBlock);
                    });
                }
            }
            else {
                var channelId = appData.channel_id;
                var timeout = genericApp.loaderShow();
                FXAPI.getChannel(channelId, function (channelData) {
                    var $subscribeButton = $('<div/>').addClass('button start green').text('Subcribe');
                    $subscribeButton.unbind('click').bind('click', function () {
                        var $subscribePage = $genericApp.find('#page_subscription');
                        $subscribePage.empty().append(ich['page_subscription']);

                        var $baseFields = $subscribePage.find('.baseFields');
                        var $sfxFields = $subscribePage.find('.sfxFields').css('padding', '0');
                        var $submitSubscriptionData = $subscribePage.find('.button.submit');
                        var $backButton = $subscribePage.find('.button.back');

                        $.each(channelData.base_fields, function (index, field) {
                            $baseFields.append($('<li/>').text(field));
                        });
                        $.each(channelData.sfx_fields, function (fieldName, field) {
                            var $label = $('<label/>').text(fieldName + ':');
                            var $input = $('<input>').attr('name', fieldName);
                            $sfxFields.append($label).append($input);
                        });

                        $submitSubscriptionData.unbind('click').bind('click', function () {
                            $submitSubscriptionData.prop('disabled', true);

                            var fields = {};

                            $.each($subscribePage.find('input'), function (index, input) {
                                var $input = $(input);
                                fields[$input.attr('name')] = $input.val();
                            });
                            var timeout = genericApp.loaderShow();

                            FXAPI.subscribeUser(channelId, FXAPI.USER_ID, fields, function (response) {
                                if(response) {
                                    genericApp.cordovaAlert('You successfully subscribed!');
                                    FXAPI.getAppObject(appData.app_id, function (fullAppData) {
                                        genericApp.fillApplicationDescription(fullAppData);
                                        genericApp.goToPage('#page_description');
                                        genericApp.loaderHide(timeout);
                                    }, function(errors) {
                                        genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                                        genericApp.loaderHide(timeout);
                                    });
                                }
                                else {
                                    genericApp.loaderHide(timeout);
                                }
                                $submitSubscriptionData.prop('disabled', false);
                                genericApp.loaderHide(timeout);

                            }, function(errors) {
                                $submitSubscriptionData.prop('disabled', false);
                                genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                                genericApp.loaderHide(timeout);
                            });


                        });
                        $backButton.unbind('click').bind('click', function () {
                            genericApp.goToPage('#page_description');
                        });

                        genericApp.goToPage($subscribePage);
                    });
                    $versionsList.append($subscribeButton);
                    genericApp.loaderHide(timeout);
                }, function(errors) {
                    genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                    genericApp.loaderHide(timeout);
                });
            }

            //fill meta data
            var $meta = $applicationDescriptionPage.find('.app_info');

            $.each(metaData, function(option, value) {
                $meta.append(ich['property_read_only']({property_name: option, property_value: value}));
            });

            // marks
            $applicationDescriptionPage.find('.active_stars').css('width', rating * 20 + '%');
            $.each(marks, function(mark, count) {
                $.each(marks, function(i, value) {
                    if(isMaxValueKnown) return;
                    else if(value > max) max = value;
                });

                isMaxValueKnown = true;
                percents = (count / max) * 100;

                var $bar = $('<div/>').addClass('rating').css('width', percents + '%'),
                    $spanWithStar = $('<span></span>').addClass('with_star').text(mark),
                    $spanWithMarksNumber = $('<span></span>').addClass('marksNumber').text(count);

                $bar.append($spanWithStar).append($spanWithMarksNumber);
                $ratingByMarks.append($bar);
            });

            // review
            FXAPI.getAppLastReview(appID, function(reviewResponse){
                //TODO [] instead of {}
                if(!(reviewResponse instanceof Array)) {
                    var $reviewsBlock = $applicationDescriptionPage.find('.app_review');
                    $reviewsBlock.show();


                    //TODO need to create correct page and shoes this button
                    var $buttonViewOtherReviews = $('<div></div>').addClass('button blue').css('display','inline-block').text('Read More');

                    $reviewsBlock.find('.active_stars').css('width', reviewResponse.rating * 20 + '%');
                    $reviewsBlock.empty().append(ich['app_review'](reviewResponse));//.append($buttonViewOtherReviews);

                    $buttonViewOtherReviews.unbind('click').bind('click', function(){
                        var timeout = genericApp.loaderShow();
                        $buttonViewOtherReviews.prop('disabled', true);
                        FXAPI.getReview(appID, function(reviewsResponse) {
                            var $applicationReviewsPage = $genericApp.find('#page_reviews').empty().append(ich['page_reviews']()),
                                $buttonBack = $applicationReviewsPage.find('.button.back'),
                                $reviews = $applicationReviewsPage.find('.app_reviews'),
                                $marks = $applicationReviewsPage.find('.app_marks');

                            $buttonBack.unbind('click').bind('click', function(){
                                genericApp.goToPage('#page_description');
                            });

                            var $tt = $applicationDescriptionPage.find('.app_marks').children().clone();
                            $tt.find('.rating').removeClass('loaded');
                            $marks.empty().append($tt);

                            $.each(reviewsResponse, function(id, review) {
                                var $currentReview = $('<div/>').addClass('separate_block app_review').append(ich['app_review'](review));
                                $currentReview.find('.active_stars').css('width', 20 * review.rating + "%");
                                $reviews.append($currentReview);
                            });

                            genericApp.goToPage($applicationReviewsPage);
                            $buttonViewOtherReviews.prop('disabled', false);
                        }, function(errors) {
                            $buttonViewOtherReviews.prop('disabled', false);
                            genericApp.loaderHide(timeout);
                            genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
                        });
                    });
                }
            }, function(errors) {
                //genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
            });
        }
        catch(e) {
            //console.log(e.message);
        }

    };

    /**
     * This method creates page with user sets for current application.
     */
    this.fillDataSetsPage = function(app, sets) {
        var timeout = genericApp.loaderShow();
        FXAPI.getAppObject(app.app_id, function(application) {
            var appName = application.title;// + ' [' + application.version + ']';
            var $dataSetsPage = ich['page_datasets']({application_name: appName, icon_large: application.icon_large });
            genericApp.$genericApp.find('#page_datasets_list').empty().append($dataSetsPage);
            var $setsList = $dataSetsPage.filter('.dataset_list');

            $.each(sets, function(setId, data) {

                var $currentSet = ich['dataset_row'](data);
                $setsList.append($currentSet);

                if(genericApp.isPhonegap()) {
                    genericApp.db.transaction(function(tx) {
                        var cmd = 'SELECT * FROM user_sets_tbl WHERE set_id=' + setId;
                        tx.executeSql(cmd, [], function(tx, res) {
                            var date = genericApp.createSignature(res.rows.item(0).synchronisation_date);
                            $currentSet.find('.downloaded').text('Downloaded: ' + date + ' ago.');
                        });
                    }, function(error) {
                        console.log(error.message);
                    })
                }

            });

            $dataSetsPage.filter('.button.back').unbind('click').bind('click', function () {
                genericApp.goToPage('#page_user_apps_info');
            });
            $dataSetsPage.filter('.button.download').unbind('click').bind('click', function () {
                var timeout = genericApp.loaderShow();

                var setsList = $.makeArray($dataSetsPage.find('.checkbox.active').map(function(){ return this.dataset.set }));

                var recursiveFillDB = function(sets, application, callback) {
                    if(sets.length == 0) {
                        callback();
                        return;
                    }
                    genericApp.fillLocalDatabase(sets.pop(), application, function() {
                        recursiveFillDB(sets, application, callback);
                    });
                };

                recursiveFillDB(setsList, application, function() {
                    genericApp.loaderHide(timeout);
                });
            });
            $dataSetsPage.find('.checkbox').unbind('click').bind('click', function () {
                var $currentCheckbox = $(this);
                var isActive = $currentCheckbox.hasClass('active');

                if(isActive) {
                    $currentCheckbox.addClass('inactive');
                    $currentCheckbox.removeClass('active');
                }
                else {
                    $currentCheckbox.removeClass('inactive');
                    $currentCheckbox.addClass('active');
                }

            });

            genericApp.loaderHide(timeout);
        }, function(errors) {
            genericApp.cordovaAlert(genericApp.createErrorMessage(errors));
            genericApp.loaderHide(timeout);
        })
    };

    /**
     * This method checks if there is no errors and call the callback.
     * If there is an error then errorcallback().
     */
    //this.ifThereIsNoErrors = function(responseJSON, callback, error_callback) {
    //    var $errors_text = 'Errors: \n';
    //    var response;
    //    try {
    //        response = JSON.parse(responseJSON);
    //    }
    //    catch (e){
    //        response = responseJSON;
    //    }
    //
    //    if(response) {
    //        if(!error_callback)
    //            error_callback = $.noop;
    //
    //        if(response.errors) {
    //            $.each(response.errors, function(error_code, errors) {
    //                $.each(errors, function(index, error) {
    //                    $errors_text += (index + '. ' + error +"\n");
    //                })
    //            });
    //            genericApp.cordovaAlert($errors_text);
    //            error_callback(responseJSON);
    //        }
    //        else {
    //            callback(responseJSON);
    //        }
    //    }
    //};

    /**
     * This method adds resize event listener.
     */
    this.addResizeEventListener = function() {
        var supportsOrientationChange = "onorientationchange" in window;
        var orientationEvent = supportsOrientationChange ? "orientationchange" : "resize";
        var actionByResize = function() {
            genericApp.recalculateSizes();
            genericApp.createKeyboardAppearListeners();

            if(genericApp.isKeyboardShows && genericApp.isMobile.any()) {
                genericApp.onShowKeyboard();
            }
        };

        window.addEventListener(orientationEvent, actionByResize, false);
        actionByResize()
    };

        /** Manipulation with styles */

    /**
     * This method starts when keyboard appears.
     */
    this.onShowKeyboard = function() {
        $('#application').addClass('with_keyboard');

        setTimeout(function () {
            window.scrollTo(document.body.scrollLeft, document.body.scrollTop);
        }, 0);

        console.log('onShowKeyboard, scrolltop');

        //console.log('with kb: ' + $('#application').hasClass('with_keyboard'));
        //console.log('with kb: ' + $('#application').hasClass('with_keyboard'));

        var $bottomNavigation = $('.fx_bottom_navigation');
        var $topNavigation = $('.fx_top_navigation');
        var isLandscapeOrient = ( genericApp.getOrientation() == 'landscape' );

        //$topNavigation.css('position', 'inherit');
        //$bottomNavigation.css({'position':'inherit', 'top': 0});




        if(isLandscapeOrient) {
            $bottomNavigation.css('height', $('.page.active').css('height'));

            var ht = parseInt($('body').css('height')) - $topNavigation.outerHeight();
            $bottomNavigation.find('ul').css('height', ht + 'px');
            //$bottomNavigation.css('padding-top', $topNavigation.outerHeight());
        }
        else {
            //$bottomNavigation.css('bottom', 0)
            //$bottomNavigation.css('position', 'inherit');
            //$('.page.active').css('')
            //$bottomNavigation.addClass('with_keyboard');
            //$pages.addClass('with_keyboard');
        }

        //$('.page, .page_content').css('padding', 0);
        genericApp.isKeyboardShows = true;
    };

    /**
     * This method contains actions by keyboard hide.
     */
    this.onHideKeyboard = function() {
        $('#application').removeClass('with_keyboard');

        var $bottomNavigation = $('.fx_bottom_navigation');

        //$('.fx_top_navigation').add($bottomNavigation).css('position', 'fixed');
        $bottomNavigation.css('height', 'auto');
        $bottomNavigation.find('ul').css('height', '100%');

        genericApp.recalculateSizes(); // for specific app
        genericApp.isKeyboardShows = false;

    };

    /**
     * This method creates event listeners for keyboard.
     */
    this.createKeyboardAppearListeners = function() {
        if(genericApp.isMobile.iOS()) {
            console.log('create new keyboard listeners');
            $('input, textarea, select')
                .unbind('focus').on('focus', genericApp.onShowKeyboard)
                .unbind('blur').on('blur', genericApp.onHideKeyboard);
            //var $body = jQuery('body');
            //
            //$(document)
            //    .on('focus', 'input', function() {
            //        $body.addClass('fixfixed');
            //    })
            //    .on('blur', 'input', function() {
            //        $body.removeClass('fixfixed');
            //    });
        }
        else if(genericApp.isMobile.Android()) {
            document.addEventListener('hidekeyboard', genericApp.onHideKeyboard, false);
            document.addEventListener('showkeyboard', genericApp.onShowKeyboard, false);
        }

    };

        /** Work with local database */

    this.initDatabase = function() {
        if(genericApp.isPhonegap()) {
            //var db = window.sqlitePlugin.openDatabase('mydb', '1.0', 'Test DB', 2 * 1024 * 1024);
            var db = window.sqlitePlugin.openDatabase('my.db');
            genericApp.db = FXAPI.db = db;
            if(db) {
                genericApp.createTableTransaction('users_tbl', {
                    user_id: 'INTEGER',
                    api_key: 'TEXT',
                    email: 'TEXT',
                    password: 'TEXT',
                    data: 'TEXT'
                });
                genericApp.createTableTransaction('user_apps_tbl', {
                    email: 'TEXT',
                    apps: 'TEXT'
                });
                genericApp.createTableTransaction('user_sets_tbl', {
                    email: 'TEXT',
                    schema_id: 'INTEGER',
                    set_id: 'INTEGER',
                    name: 'TEXT',
                    display_name: 'TEXT',
                    synchronisation_date: 'TEXT'
                });
            }
        }
    };
    /**
     * This method creates new table in local database.
     */
    this.createTableTransaction = function(tableName, fields) {
        if(genericApp.db) {
            genericApp.db.transaction(function(tx){
                var fieldsList = [];
                $.each(fields, function(name, value) {
                    fieldsList.push(name + ' ' + value);
                });
                var command = 'CREATE TABLE IF NOT EXISTS ' + tableName + ' (' +  fieldsList.join(', ')+' )';
                tx.executeSql(command);
            }, function(e) {
                console.log("TABLE: " + tableName + ", ERROR: " + e.message);
            });
        }
    };
    /**
     * This method drops new table in local database.
     */
    this.dropTableTransaction = function(tableName) {
        if(genericApp.db) {
            genericApp.db.transaction(function(tx){
                tx.executeSql('DROP TABLE ' + tableName );
            }, function(e) {
                console.log("TRY TO DROP TABLE: " + tableName + ", ERROR: " + e.message);
            });
        }
    };
    /**
     * This method fill new table in local database.
     * @exceptions is array which contains properties which will need to ignore.
     * @data is object which contains data which will be in database.
     */
    this.fillTableTransaction = function(tableName, exceptions, data) {
        if(genericApp.db) {
            genericApp.db.transaction(function (tx) {
                var properties = [];
                var values = [];

                //try {
                    $.each(data, function (fieldName, val) {
                        if ($.inArray(fieldName, exceptions) < 0) {
                            properties.push(fieldName);

                            if (val === null || val === undefined) {
                                values.push("");
                            }

                            else if (typeof val == 'object') {
                                values.push(JSON.stringify(val));
                            }
                            else {
                                values.push(val);
                            }
                        }
                    });

                    var str = "?";
                    for (var i = 1; i < properties.length; i++) {
                        str += ', ?';
                    }
                    var command = 'INSERT INTO ' + tableName + ' (' + properties.join(', ') + ') VALUES (' + str + ')';
                    tx.executeSql(command, values, function (tx, queryRes) { });
                //}
                //catch (e) {
                    //console.log(e.message);
                //}
            }, function(e) {
                console.log("TRY TO FILL TABLE: " + tableName + ", ERROR: " + e.message);
            });
        }
    };
    /**
     * This method fills database, when applications starts on current device first time.
     * @dataset is selected dataset,
     * @currentApplication is application object with data about: server URLs, schema, etc.
     */
    this.fillLocalDatabase = function(dataset, currentApplication, callback) {
        $.extend(DFXAPI, {
            SITE_URL: currentApplication.base_url,
            BASE_API_URL: currentApplication.base_api_url,
            API_KEY: currentApplication.channel_key,
            SCHEMA_ID: currentApplication.remote_schema_id || application.schema_id,
            UPLOADS_URL: currentApplication.base_url + "uploads/",
            encryptKey: currentApplication.channel_token,
            db: genericApp.db
        });
        if (typeof callback !== "function") {
            callback = $.noop;
        }
        DFXAPI.getSynchTree(dataset, function(synchronisationTree) {
            //console.log('tree', synchronisationTree);
            DFXAPI.getSynchData(dataset, function(synchronisationData){
                //console.log('data', synchronisationData);
                if(synchronisationTree.length == 0 || synchronisationData.length == 0 || !genericApp.isPhonegap()) {
                    callback();
                    return
                }

                if(typeof window.sqlitePlugin !== 'undefined') {
                    try {
                        var createTableTransaction = genericApp.createTableTransaction;
                        var dropTableTransaction = genericApp.dropTableTransaction;
                        var fillTableTransaction = genericApp.fillTableTransaction;
                        var enumTypesTableName = 'enum_type_tbl';
                        var enumFieldsTableName = 'enum_field_tbl';
                        var linkTypesTableName = 'link_type_tbl';
                        var linksTableName = 'link_tbl';
                        var formsTableName = 'form_tbl';
                        var queriesTableName = 'query_tbl';
                        var objectTypesTableName = 'object_type_tbl';
                        var appsTableName = 'apps_tbl';
                        var userSetsTableName = 'user_sets_tbl';

                        // types and objects
                        $.each(synchronisationData.objects, function(objectTypeId, objects) {
                            var tableName = 'object_type_' + objectTypeId;
                            var fieldTypes = {
                                object_id: 'INTEGER',
                                schema_id: 'INTEGER',
                                set_id: 'INTEGER',
                                system: 'INTEGER',
                                created: 'INTEGER',
                                modified: 'INTEGER',
                                name: 'TEXT',
                                display_name: 'TEXT'
                            };
                            var params = {};
                            var thereAreObjects = false;

                            for (var firstObjectId in objects) {
                                thereAreObjects = true;

                                $.each(objects[firstObjectId], function(param, value) {
                                    params[param] = (fieldTypes[param]) ? fieldTypes[param] : 'TEXT';
                                });

                                break;
                            }

                            if(thereAreObjects) {
                                dropTableTransaction(tableName);
                                createTableTransaction(tableName, params);

                                $.each(objects, function(objectId, objectData) {
                                    fillTableTransaction(tableName, [], objectData);
                                });
                            }
                        });

                        // get current app params and values
                        var currentDate = new Date().getTime();
                        currentApplication.synchronisation_date = currentDate;

                        var params = {};
                        params.version_id = 'TEXT';
                        params.version = 'TEXT';
                        params.style = 'TEXT';
                        params.code = 'TEXT';
                        params.installed = 'TEXT';
                        params.dev_keys = 'TEXT';

                        $.each(currentApplication, function(appOption) {
                            params[appOption] = 'TEXT';
                        });
                        // create table with apps types
                        createTableTransaction(appsTableName, params);


                        if(currentApplication.versions) {
                            $.each(currentApplication.versions, function(id, versionData) {
                                if(versionData.installed) {
                                    var versionObject = $.extend({ version_id: id }, currentApplication, versionData);
                                    delete versionObject.versions;
                                    FXAPI.db.transaction(function(tx){
                                        tx.executeSql('DELETE FROM ' + appsTableName + ' WHERE version_id="' + id + '"', []);
                                    });
                                    fillTableTransaction(appsTableName, [], versionObject);
                                }
                            });
                        }



                        // apps and sets are filling
                        var setData = {
                            name: DFXAPI.dataSets[dataset].name,
                            display_name: DFXAPI.dataSets[dataset].display_name,
                            email: FXAPI.currentUserMail,
                            schema_id: DFXAPI.SCHEMA_ID,
                            set_id: DFXAPI.dataSets[dataset].object_id,
                            synchronisation_date: currentDate
                        };

                        FXAPI.db.transaction(function(tx){
                            tx.executeSql('DELETE FROM ' + userSetsTableName + ' WHERE set_id="' + dataset + '" AND email="' + FXAPI.currentUserMail + '"', []);
                        });

                        fillTableTransaction(userSetsTableName, [], setData);

                        // create table with object types
                        dropTableTransaction(objectTypesTableName);
                        createTableTransaction(objectTypesTableName, {
                            object_type_id: 'INTEGER',
                            schema_id: 'INTEGER',
                            system: 'INTEGER',
                            revisions_number: 'INTEGER',
                            prefix: 'TEXT',
                            name_format: 'TEXT',
                            name: 'TEXT',
                            display_name: 'TEXT',
                            description: 'TEXT'
                        });

                        // create table with enum fields
                        dropTableTransaction(enumFieldsTableName);
                        createTableTransaction(enumFieldsTableName, {
                            enum_field_id: 'INTEGER',
                            enum_type_id: 'INTEGER',
                            label: 'TEXT',
                            value: 'TEXT'
                        });

                        // create table with enum types
                        dropTableTransaction(enumTypesTableName);
                        createTableTransaction(enumTypesTableName, {
                            enum_type_id: 'INTEGER',
                            system: 'INTEGER',
                            schema_id: 'INTEGER',
                            name: 'TEXT'
                        });

                        // create link types table
                        dropTableTransaction(linkTypesTableName);
                        createTableTransaction(linkTypesTableName, {
                            object_type_1_id: 'INTEGER',
                            object_type_2_id: 'INTEGER',
                            description: 'TEXT',
                            relation: 'INTEGER',
                            system: 'INTEGER'
                        });

                        // create links table
                        dropTableTransaction(linksTableName);
                        createTableTransaction(linksTableName,  {
                            object_type_1_id: 'INTEGER',
                            object_type_2_id: 'INTEGER',
                            object_1_id: 'INTEGER',
                            object_2_id: 'INTEGER',
                            meta: 'TEXT'
                        });

                        // create forms table
                        dropTableTransaction(formsTableName);
                        createTableTransaction(formsTableName, {
                            form_id: 'INTEGER',
                            object_type: 'INTEGER',
                            code: 'TEXT',
                            link_with_user: 'INTEGER',
                            filter_by_set: 'INTEGER',
                        });

                        // create queries table
                        dropTableTransaction(queriesTableName);
                        createTableTransaction(queriesTableName,{
                            query_id: 'INTEGER',
                            main_type: 'INTEGER',
                            code: '',
                            result: 'TEXT'
                        });

                        // fill enum tables
                        var fieldIndex = 0;
                        $.each(synchronisationTree.enums, function(id, data) {
                            // fill enum types
                            fillTableTransaction(enumTypesTableName, ['fields'], data);

                            // fill enum fields
                            var enumTypeId = data.enum_type_id;
                            $.each(data.fields, function(value, name) {
                                fillTableTransaction(enumFieldsTableName, [], {
                                    enum_field_id: fieldIndex,
                                    enum_type_id: enumTypeId,
                                    label: name,
                                    value : value
                                });
                                fieldIndex++;
                            });
                        });

                        // fill table with link types
                        $.each(synchronisationTree.link_types, function(id, data) {
                            fillTableTransaction(linkTypesTableName, [], data);
                        });

                        // fill table with links
                        $.each(synchronisationData.links, function(id, data) {
                            fillTableTransaction(linksTableName, [], data);
                        });

                        // fill table with forms
                        $.each(synchronisationData.forms, function(id, data) {
                            data.form_id = id;
                            fillTableTransaction(formsTableName, [], data);
                        });

                        // fill table with queries
                        $.each(synchronisationTree.queries, function(id, queryData) {
                            var query = {
                                query_id: id,
                                result : JSON.stringify(synchronisationData.queries[id]),
                                main_type: queryData.main_type,
                                code: queryData.code
                            };
                            fillTableTransaction(queriesTableName, [], query);
                        });

                        genericApp.printDatabase();

                        callback();

                    }
                    catch(e) {
                        console.log('sqlite filling EXCEPTION: ', e.message);
                        callback();
                    }
                }
                else {
                    callback();
                }


            }, function() {
                callback();
            });
        }, function() {
            callback();
        });
    };

    this.printDatabase = function() {

        var obj = {};
        var tables = [ 'enum_type_tbl', 'enum_field_tbl', 'link_type_tbl', 'link_tbl',  'form_tbl',
            'query_tbl', 'object_type_tbl', 'users_tbl', 'user_apps_tbl', 'user_sets_tbl', 'apps_tbl' ];

        $.each(tables, function(k, tableName) {
            genericApp.db.transaction(function(tx) {
                tx.executeSql('SELECT * FROM ' + tableName, [], function(tx, res) {
                    var items = [];
                    for (var i = 0; i < res.rows.length; i++) {
                        items.push(res.rows.item(i));
                    }
                    obj[tableName] = items;
                });
            }, function(e){
                console.log("PRINT ERROR FOR TABLE: " + tableName + ", ERROR: " + e.message);
            });
        });
    };



    /**
     * This method creates text message for sync data at apps info page.
     * @ms is date in milliseconds.
     */
    this.createSignature = function(ms) {
        var DateDiff = {
            inSeconds: function(d1, d2) {
                var t2 = d2.getTime();
                var t1 = d1.getTime();

                return parseInt((t2-t1)/(1000));
            },
            inMins: function(d1, d2) {
                var t2 = d2.getTime();
                var t1 = d1.getTime();

                return parseInt((t2-t1)/(60*1000) % 60);
            },
            inHours: function(d1, d2) {
                var t2 = d2.getTime();
                var t1 = d1.getTime();

                return parseInt((t2-t1)/(3600*1000));
            },
            inDays: function(d1, d2) {
                var t2 = d2.getTime();
                var t1 = d1.getTime();

                return parseInt((t2-t1)/(24*3600*1000));
            },
            inMonths: function(d1, d2) {
                var d1Y = d1.getFullYear();
                var d2Y = d2.getFullYear();
                var d1M = d1.getMonth();
                var d2M = d2.getMonth();

                return (d2M+12*d2Y)-(d1M+12*d1Y);
            }
        };
        var currentDate = new Date();
        var date = new Date(parseInt(ms));
        var days = DateDiff.inDays(date, currentDate);
        var resString = "";

        if(days > 30)  {
            resString = 'about ' + DateDiff.inMonths(date, currentDate) + ' month';
        }
        else if(days > 7) {
            resString = 'about ' + days +' days';
        }
        else {
            var mins = DateDiff.inMins(date, currentDate) % 60;
            var hours = DateDiff.inHours(date, currentDate) % 24;

            if(mins >= 30)
                hours++;

            if(days > 1) {
                resString = days + ' days, ~' + hours + ' hours';
            }
            else if (hours > 1){
                resString = 'about ' + hours + ' hours';
            }
            else {
                if(DateDiff.inSeconds(date, currentDate) % 60 >= 30) {
                    mins++;
                }
                resString = 'about ' + mins + ' mins';
            }
        }

        return resString;
    };

    var timeouts = genericApp.timeouts = [];

    this.loaderShow = function() {
        //if(!FXAPI.offlineMode) {
            var $loader = $('#loader');
            $(window).scrollTop(0);

            if($loader.length == 0) {
                $loader = $('<div>').attr('id', 'loader');
                $loader = ich['loader_spinner']();
                $('body').append($loader)
            }

            $loader.show();

            var timeout = setTimeout(function() {
                if($loader.is(":visible")) {
                    genericApp.loaderHide(timeout);
                }
            }, 30000);

            $('body').bind('touchmove', function(e){e.preventDefault()});

            timeouts.push(timeout);
            return timeout;

        //}
    };
    this.loaderHide = function(id) {
        if(typeof id !== 'undefined' && id != null) {
            clearTimeout(id);
            timeouts.splice(timeouts.indexOf(id), 1);
        }

        if(timeouts.length <= 0) {
            $('body').unbind('touchmove')
            $('#loader').hide();
        }
    };

    this.createErrorMessage = function(data) {
        if(!data) return;
        if(isEmpty(data)) return;

        var i = 1;
        var text = "";
        if(typeof data.errors !== 'undefined' && data.errors != null) {
            $.each(data.errors, function(_, msg) {
                text = text + i + '. ' + msg + "\n";
                i++;
            });
        }
        else {
            text = i + '. ' + data;
        }

        return text;
    };

    this.cordovaAlert = function (msg) {
        if(!msg) return;
        var alertCallback = $.noop;

        if(!genericApp.isPhonegap()) {
            alert(msg);
        }
        else {
            navigator.notification.alert(msg, alertCallback, 'Flexiweb', 'OK');
        }
    };
    this.cordovaConfirm = function (msg, alertCallback) {
        if(!alertCallback) alertCallback = $.noop;

        if(!genericApp.isPhonegap()) {
            return confirm(msg);
        }
        else {
            return navigator.notification.confirm(msg, alertCallback, 'Flexiweb', ['OK','Cancel']);
        }
    };

    /* PUSHES */

    this.pushes = {};
    this.actionOnPush = function(notification) {
        var data = $.extend({}, notification);
        data.icon_large = "https://flexilogin.com/flexiweb/chnl_img/34_large.png";
        //data.text = notification.aps.alert;
        data.text = notification.text;
        data.title = notification.title;
        //genericApp.addPush(data);

        var pushIndex;
        for (var i in genericApp.pushes) {
            pushIndex = i;
        }

        var id = typeof pushIndex !== 'undefined' ? parseInt(pushIndex) : 0;
        data.id = id + 1;

        genericApp.pushes[data.id] = data;


        var $page = genericApp.$genericApp.find('#page_notifications');

        var $notificationsIcon = genericApp.$genericApp.find('.notifications');

        if(!$notificationsIcon.hasClass('badge')) {
            $notificationsIcon.addClass('badge');
        }
        //$notificationsIcon.html(id + 1);
        $notificationsIcon.html('');

        genericApp.fillContent(genericApp.pushes, $page, $.noop, 'notification_list');
        genericApp.initNavigation($page);

    };

    this.onPushwooshiOSInitialized = function (pushToken) {
        var pushNotification = cordova.require("com.pushwoosh.plugins.pushwoosh.PushNotification");
        //retrieve the tags for the device
        pushNotification.getTags(
            function(tags) {
                console.warn('tags for the device: ' + JSON.stringify(tags));
            },
            function(error) {
                console.warn('get tags error: ' + JSON.stringify(error));
            }
        );

        //example how to get push token at a later time
        pushNotification.getPushToken(
            function(token)	{
                console.warn('push token device: ' + token);
            }
        );

        //example how to get Pushwoosh HWID to communicate with Pushwoosh API
        pushNotification.getPushwooshHWID(
            function(token) {
                console.warn('Pushwoosh HWID: ' + token);
            }
        );

        //start geo tracking.
        //pushNotification.startLocationTracking();
    };
    this.registerPushwooshIOS = function() {
        var pushNotification = cordova.require("com.pushwoosh.plugins.pushwoosh.PushNotification");

        //set push notification callback before we initialize the plugin
        document.addEventListener('push-notification', function(event) {
            //get the notification payload
            var notification = event.notification;

            //display alert to the user for example
            //alert(notification.aps.alert);

            //to view full push payload
            //alert(JSON.stringify(notification));

            genericApp.actionOnPush({text: notification.aps.alert});

            //clear the app badge
            pushNotification.setApplicationIconBadgeNumber(0);
        });

        //initialize the plugin
        pushNotification.onDeviceReady({pw_appid:"51C72-B468B"});

        //register for pushes
        pushNotification.registerDevice(
            function(status) {
                var deviceToken = status['deviceToken'];
                console.warn('registerDevice: ' + deviceToken);
                genericApp.onPushwooshiOSInitialized(deviceToken);
            },
            function(status) {
                console.warn('failed to register : ' + JSON.stringify(status));
                //alert(JSON.stringify(['failed to register ', status]));
            }
        );

        //reset badges on start
        pushNotification.setApplicationIconBadgeNumber(0);
    };

    this.onPushwooshAndroidInitialized =  function (pushToken){
        //output the token to the console
        console.warn('push token: ' + pushToken);

        var pushNotification = cordova.require("com.pushwoosh.plugins.pushwoosh.PushNotification");

        //if you need push token at a later time you can always get it from Pushwoosh plugin
        pushNotification.getPushToken(function(token) {
            console.warn('push token: ' + token);
        });

        //and HWID if you want to communicate with Pushwoosh API
        pushNotification.getPushwooshHWID(function(token) {
            console.warn('Pushwoosh HWID: ' + token);
        });

        pushNotification.getTags(
            function(tags)	{ console.warn('tags for the device: ' + JSON.stringify(tags));},
            function(error) { console.warn('get tags error: ' + JSON.stringify(error));}
        );


        //set multi notificaiton mode
        //pushNotification.setMultiNotificationMode();
        //pushNotification.setEnableLED(true);

        //set single notification mode
        //pushNotification.setSingleNotificationMode();

        //disable sound and vibration
        //pushNotification.setSoundType(1);
        //pushNotification.setVibrateType(1);

        pushNotification.setLightScreenOnNotification(false);

        //setting list tags
        //pushNotification.setTags({"MyTag":["hello", "world"]});

        //settings tags
        pushNotification.setTags({deviceName:"hello", deviceId:10},
            function(status) {
                console.warn('setTags success');
            },
            function(status) {
                console.warn('setTags failed');
            }
        );

        //Pushwoosh Android specific method that cares for the battery
        //pushNotification.startGeoPushes();
    };
    this.registerPushwooshAndroid = function () {

        var pushNotification = cordova.require("com.pushwoosh.plugins.pushwoosh.PushNotification");

        //set push notifications handler
        document.addEventListener('push-notification',
            function(event) {
                var title = event.notification.title;
                var userData = event.notification.userdata;

                //dump custom data to the console if it exists
                if(typeof(userData) != "undefined") {
                    console.warn('user data: ' + JSON.stringify(userData));
                }

                //and show alert
                //alert(title);

                genericApp.actionOnPush(event.notification);

                //stopping geopushes
                //pushNotification.stopGeoPushes();
            }
        );

        //initialize Pushwoosh with projectid: "GOOGLE_PROJECT_ID", appid : "PUSHWOOSH_APP_ID". This will trigger all pending push notifications on start.
        pushNotification.onDeviceReady({ projectid: "980290142657", appid : "B244C-62E5C" });

        //register for push notifications
        pushNotification.registerDevice(
            function(token) {
                alert(token);
                //callback when pushwoosh is ready
                genericApp.onPushwooshAndroidInitialized(token);
            },
            function(status) {
                alert("failed to register: " +  status);
                console.warn(JSON.stringify(['failed to register ', status]));
            }
        );
    };

    this.onPushwooshWPInitialized = function () {
        var pushNotification = cordova.require("com.pushwoosh.plugins.pushwoosh.PushNotification");

        //if you need push token at a later time you can always get it from Pushwoosh plugin
        pushNotification.getPushToken(
            function (token) {
                alert('push token: ' + token);
            }
        );

        //and HWID if you want to communicate with Pushwoosh API
        pushNotification.getPushwooshHWID(
            function (token) {
                alert('Pushwoosh HWID: ' + token);
            }
        );

        //settings tags
        pushNotification.setTags({ tagName: "tagValue", intTagName: 10 },
            function (status) {
                alert('setTags success: ' + JSON.stringify(status));
            },
            function (status) {
                console.warn('setTags failed');
            }
        );

        pushNotification.getTags(
            function (status) {
                alert('getTags success: ' + JSON.stringify(status));
            },
            function (status) {
                console.warn('getTags failed');
            }
        );
    };
    this.registerPushwooshWP = function () {
        var pushNotification = cordova.require("com.pushwoosh.plugins.pushwoosh.PushNotification");

        //set push notification callback before we initialize the plugin
        document.addEventListener('push-notification', function (event) {
            //get the notification payload
            var notification = event.notification;

            //display alert to the user for example
            //alert(JSON.stringify(notification));
            genericApp.actionOnPush(notification);
        });

        //initialize the plugin
        pushNotification.onDeviceReady({ appid: "B244C-62E5C", serviceName: "" });

        //register for pushes
        pushNotification.registerDevice(
            function (status) {
                var deviceToken = status;
                console.warn('registerDevice: ' + deviceToken);
                alert("push token is " + deviceToken);
                genericApp.onPushwooshWPInitialized();
            },
            function (status) {
                console.warn('failed to register : ' + JSON.stringify(status));
                alert(JSON.stringify(['failed to register ', status]));
            }
        );
    };

    genericApp.addResizeEventListener();

    if (navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry|IEMobile)/)) {
        document.addEventListener("deviceready", genericApp.init, false);
    } else {
        //onDeviceReady(); //this is the browser
        genericApp.init();
    }
};