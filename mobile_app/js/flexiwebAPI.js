FXAPI = {
    API_KEY: localStorage.getItem("GLOBAL_API_KEY"),
    SERVER_URL: "https://flexilogin.com/flexiweb/api/v1/",
    offlineMode: false,
    timeout: 30000,

    // get all applications if search is empty
    getApplications : function(portal, search, offsetVal, limit, callback) {
        if(FXAPI.offlineMode) {
            callback({"errors":{"getApplications":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "app/search",
                data:{
                    search_string: search,
                    portal_id: portal,
                    offset: offsetVal,
                    limit: limit
                }
            };

            FXAPI.generalFxServerRequest(args, callback, function() {
                callback({});
            });
        }
    },
    // get all users applications
    getInstalledApplications : function(callback, error_callback) {
        var usersAppsTable = 'user_apps_tbl';
        var email = FXAPI.currentUserMail;
        var usersAppsList = {};

        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            var appsList = [];
            FXAPI.db.transaction(function(tx){
                tx.executeSql('SELECT * FROM ' + usersAppsTable + ' WHERE email="' + email + '"', [], function(tx, res){
                    try {
                        appsList = JSON.parse(res.rows.item(0).apps);
                        //console.log('getInstalledApplications :: appsList', appsList);

                        FXAPI.db.transaction(function(tx){
                            tx.executeSql('SELECT * FROM apps_tbl WHERE version_id IN (' + appsList.join(',') + ')', [], function (tx, res) {
                                for (var i = 0; i < res.rows.length; i++) {
                                    var app = res.rows.item(i);
                                    app.installed = app.is_subscibed = true;
                                    usersAppsList[app.version_id] = app;
                                }
                                callback(usersAppsList);
                            });
                        }, function(error){
                            error_callback(error.message);
                        });
                    }
                    catch(e) {
                        error_callback({"errors":{"getInstalledApplications":["Parse error."]},"error_data":[]});
                    }
                });
            }, function(errors) {
                error_callback(errors.message);
            });
        }
        else {
            var args = {
                function: "app/user"
            };

            var action = function(data) {
                var versions = [];

                $.each(data, function(versionId) {
                    versions.push(versionId);
                });

                var dt = {
                    email: email,
                    apps: JSON.stringify(versions)//versions.join(',')
                };

                if(FXAPI.isPhonegap()) {
                    FXAPI.db.transaction(function(tx){
                        tx.executeSql('DELETE FROM ' + usersAppsTable + ' WHERE email="' + email + '"', []);
                    });
                }

                FXAPI.fillTableTransaction(usersAppsTable, [], dt);
                callback(data);
            };
            if(!error_callback) error_callback = function() { callback({}) };

            FXAPI.generalFxServerRequest(args, action, error_callback);
        }
    },
    // get app object
    getAppObject : function(id, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            FXAPI.db.transaction(function(tx){
                tx.executeSql('SELECT * FROM apps_tbl WHERE app_id=' + id, [], function (tx, res) {
                    var app = res.rows.item(0);
                    app.installed = app.is_subscribed = 1;
                    app.versions = {};

                    try { app.meta = JSON.parse(app.meta); }
                    catch(e) { console.log('meta didn`t parsed: ', e.message); }

                    try { app.rating_by_marks = JSON.parse(app.rating_by_marks); }
                    catch(e) { console.log('rating_by_marks didn`t parsed: ', e.message); }

                    for (var i = 0; i < res.rows.length; i++) {
                        app.versions[app.version_id] = {
                            code: app.code,
                            style: app.style,
                            installed: 1,
                            version: app.version
                        };
                    }

                    delete app.code;
                    delete app.style;
                    delete app.version;
                    delete app.version_id;
                    delete app.visible_label;

                    callback(app);
                });
            }, function(errors){ error_callback(errors.message); });
            //callback(app);
        }
        else {
            var args = {
                function: "app/object",
                data: {
                    app_id: id
                }
            };
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },
    // get app versions
    getVersionData : function(id, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getVersionData":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "app/version_data",
                data: {
                    app_id: id
                }
            };
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },

    // install app
    installApplication : function(id, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"installApplication":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "app/install",
                data: {
                    app_id: id
                }
            };
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },
    // remove app
    uninstallApplication : function(appId, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"uninstallApplication":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "app/uninstall",
                type: "POST",
                method: "DELETE",
                data: {
                    app_id: appId
                }
            };
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },

    // get channel data
    getChannel: function(id, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getChannel":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "channel/subscription",
                data: {
                    channel_id: id
                }
            };

            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },
    // subscribe user to channel
    subscribeUser : function(id, user_id, fields, callback, error_callback) {
//        POST channel/subscription : 'channel_id, 'api_key', 'fields' - array
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"subscribeUser":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "channel/subscription",
                type: 'POST',
                data: {
                    channel_id: id,
                    user_id: user_id,
                    sfx_fields: fields
                }
            };
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },

    // get last review
    getAppLastReview: function(applicationId, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getAppLastReview":["getAppLastReview. This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "review/last",
                data: {
                    object_id: applicationId,
                    relative: 'app'
                }
            };

            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },
    // get review
    getReview: function(applicationId, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({ errors: [ 'This method doesn`t works in offline mode' ]});
        }
        else {
            var args = {
                function: "review/for",
                data: {
                    object_id: applicationId,
                    relative: 'app'
                }
            };
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },

    // login method
    tryLoginUser: function(arguments, callback, error_callback) {
        var usersTable = 'users_tbl';
        var email = arguments.email;
        var pwd = arguments.password;

        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            FXAPI.db.transaction(function(tx){
                tx.executeSql('SELECT * FROM ' + usersTable + ' WHERE email="' + email + '" AND password="' + pwd + '"', [], function(tx, res) {
                    var userData;
                    if(res.rows.length > 0) {
                        try {
                            userData =  JSON.parse(res.rows.item(0).data);
                        }
                        catch(e) {
                            userData = res.rows.item(0);
                            delete userData.data;
                        }

                    }
                    else {
                        userData = { errors: 'There is no current user in local db.' };
                    }
                    callback(userData);
                });
            }, function(errors) {
                error_callback(errors.message);
            });
        }
        else {
            var args = {
                function: "app/login",
                data: arguments
            };

            var action = function(response) {
                if(response.api_key) {

                    if(FXAPI.isPhonegap()) {
                        FXAPI.db.transaction(function(tx){
                            tx.executeSql('DELETE FROM ' + usersTable + ' WHERE email="' + email + '"', [] );
                        });

                        var newUser = {
                            user_id: response.user_id,
                            api_key: response.api_key,
                            email: email,
                            password: pwd,
                            data: JSON.stringify(response)
                        };

                        FXAPI.db.transaction(function(tx){
                            tx.executeSql('SELECT * FROM ' + usersTable, []);
                        });

                        FXAPI.fillTableTransaction(usersTable, [], newUser);
                    }

                }
                callback(response);
            };
            FXAPI.generalFxServerRequest(args, action, error_callback);
        }
    },
    // get user data
    getUserData: function(callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            //callback({});
            //callback({"errors":{"getUserData":["This method doesn`t works in offline mode."]},"error_data":[]});
            callback(FXAPI.userData);
        }
        else {
            var args = {
                function: "user/by_api_key"
            };
            //FXAPI.generalFxServerRequest(args, callback, error_callback);
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },
    // save user data
    saveUserData: function(object, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"saveUserData":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "user/fields",
                type: 'PUT',
                data: object
            };
            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },

    getPortals: function(callback, error_callback) {
        //TODO portals for offline mode
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            callback({});
        }
        else {
            var args = {
                function: "user/portals"
            };

            FXAPI.generalFxServerRequest(args, callback, error_callback);
        }
    },

    // general request
    generalFxServerRequest : function( args, callback, error_callback ) {
        if(typeof args.type === 'undefined' || args.type == null)
            args.type = "GET";

        if(typeof error_callback !== 'function') {
            console.log(args.function + ' has no error_clb');
            error_callback = $.noop();
        }

        $.ajax({
            url: FXAPI.SERVER_URL + args.function,
            type: args.type,
            method: args.method,
            timeout: FXAPI.timeout,

            data: $.extend({
                api_key: FXAPI.GLOBAL_API_KEY
            }, args.data)

        })
            .error(function(error, text, response) {
                //console.log('error', response);
                //var msg = "Unknown error.";
                //if(text == 'timeout') msg = 'Connection timed out';
                //if(text == 'error')  msg = error.statusText;

                var msg = error.status == 0 ? 'Connection timed out' : error.statusText;
                error_callback(msg);
            })
            .success(function(data, text, response){
                //console.log('success', response);
                // flexiweb error
                if(typeof data.errors !== 'undefined') {
                    //var $errors_text = 'Errors: \n';
                    //$.each(data.errors, function(error_code, errors) {
                    //    $.each(errors, function(index, error) {
                    //        $errors_text += (index + '. ' + error +"\n");
                    //    })
                    //});
                    //FXAPI.cordovaAlert($errors_text);
                    error_callback(data);
                }
                // normal data
                else {
                    callback(data);
                }
            })
            .complete(function(data, text, response) {
                if(text == 'timeout') return;
                if(text == 'error') return;
                if(text == 'success') return;
                console.log('strange error: ', text);
            });
    }
};
    DFXAPI = {
    dataSets: [],
    activeDataSet: 0,
    timeout: 30000,

    encryptType: {cipherMode: 0, outputType: 1 },

    // general request
    generalRequest: function(args, callback, error_callback) {
        var toHex = function(string) {
            var i, f = 0,
                a = [];

            string += '';
            f = string.length;

            for (i = 0; i < f; i++) {
                a[i] = string.charCodeAt(i).toString(16).replace(/^([\da-f])$/, "0$1");
            }

            return a.join('');
        };
        var fromHex = function(string) {
            var res = [];
            for(var i=0; i < string.length; i+=2) {
                res.push(String.fromCharCode(parseInt(string.substr(i,2), 16))) ;
            }
            return res.join("");
        };

        if(typeof args.type === 'undefined' || args.type == null)
            args.type = "GET";

        var data = $.extend({
            schema_id: DFXAPI.SCHEMA_ID
        }, args.data);

        var bf = new Blowfish(DFXAPI.encryptKey);
        var encrypted = toHex(bf.encrypt(JSON.stringify(data) , undefined));

        var ajaxProperties = {
            url: this.BASE_API_URL + args.function,
            type: args.type,
            method: args.method,
            data: { data: encrypted },
            timeout: DFXAPI.timeout
        };


        if(!error_callback) {
            console.log(args.function + ' has no error_clb ');
            error_callback = $.noop;
        }

        ajaxProperties.data.api_key = DFXAPI.API_KEY;
        $.ajax(ajaxProperties).always(function(data){
            if(!!data.status && data.status != 200) {
                var msg = data.status == 0 ? 'Connection timed out' : data.statusText;
                //FXAPI.cordovaAlert('Error: ' + msg  + '.\n ' );
                error_callback(msg);
            }
            // flexiweb error
            else if(typeof data.errors !== 'undefined') {
                //var $errors_text = 'Errors: \n';
                //
                //$.each(data.errors, function(error_code, errors) {
                //    $.each(errors, function(index, error) {
                //        $errors_text += (index + '. ' + error +"\n");
                //    })
                //});
                //FXAPI.cordovaAlert($errors_text);
                error_callback(data);
            }
            // normal data
            else {
                var decryptedData = bf.decrypt( fromHex(data.responseText), undefined);
                var dataLength = decryptedData.length;
                var shortStr = decryptedData.slice(dataLength - 8);
                decryptedData = decryptedData.slice(0, dataLength - 8);

                var re = /[\0-\x1F\x7F-\x9F\xAD\u0378\u0379\u037F-\u0383\u038B\u038D\u03A2\u0528-\u0530\u0557\u0558\u0560\u0588\u058B-\u058E\u0590\u05C8-\u05CF\u05EB-\u05EF\u05F5-\u0605\u061C\u061D\u06DD\u070E\u070F\u074B\u074C\u07B2-\u07BF\u07FB-\u07FF\u082E\u082F\u083F\u085C\u085D\u085F-\u089F\u08A1\u08AD-\u08E3\u08FF\u0978\u0980\u0984\u098D\u098E\u0991\u0992\u09A9\u09B1\u09B3-\u09B5\u09BA\u09BB\u09C5\u09C6\u09C9\u09CA\u09CF-\u09D6\u09D8-\u09DB\u09DE\u09E4\u09E5\u09FC-\u0A00\u0A04\u0A0B-\u0A0E\u0A11\u0A12\u0A29\u0A31\u0A34\u0A37\u0A3A\u0A3B\u0A3D\u0A43-\u0A46\u0A49\u0A4A\u0A4E-\u0A50\u0A52-\u0A58\u0A5D\u0A5F-\u0A65\u0A76-\u0A80\u0A84\u0A8E\u0A92\u0AA9\u0AB1\u0AB4\u0ABA\u0ABB\u0AC6\u0ACA\u0ACE\u0ACF\u0AD1-\u0ADF\u0AE4\u0AE5\u0AF2-\u0B00\u0B04\u0B0D\u0B0E\u0B11\u0B12\u0B29\u0B31\u0B34\u0B3A\u0B3B\u0B45\u0B46\u0B49\u0B4A\u0B4E-\u0B55\u0B58-\u0B5B\u0B5E\u0B64\u0B65\u0B78-\u0B81\u0B84\u0B8B-\u0B8D\u0B91\u0B96-\u0B98\u0B9B\u0B9D\u0BA0-\u0BA2\u0BA5-\u0BA7\u0BAB-\u0BAD\u0BBA-\u0BBD\u0BC3-\u0BC5\u0BC9\u0BCE\u0BCF\u0BD1-\u0BD6\u0BD8-\u0BE5\u0BFB-\u0C00\u0C04\u0C0D\u0C11\u0C29\u0C34\u0C3A-\u0C3C\u0C45\u0C49\u0C4E-\u0C54\u0C57\u0C5A-\u0C5F\u0C64\u0C65\u0C70-\u0C77\u0C80\u0C81\u0C84\u0C8D\u0C91\u0CA9\u0CB4\u0CBA\u0CBB\u0CC5\u0CC9\u0CCE-\u0CD4\u0CD7-\u0CDD\u0CDF\u0CE4\u0CE5\u0CF0\u0CF3-\u0D01\u0D04\u0D0D\u0D11\u0D3B\u0D3C\u0D45\u0D49\u0D4F-\u0D56\u0D58-\u0D5F\u0D64\u0D65\u0D76-\u0D78\u0D80\u0D81\u0D84\u0D97-\u0D99\u0DB2\u0DBC\u0DBE\u0DBF\u0DC7-\u0DC9\u0DCB-\u0DCE\u0DD5\u0DD7\u0DE0-\u0DF1\u0DF5-\u0E00\u0E3B-\u0E3E\u0E5C-\u0E80\u0E83\u0E85\u0E86\u0E89\u0E8B\u0E8C\u0E8E-\u0E93\u0E98\u0EA0\u0EA4\u0EA6\u0EA8\u0EA9\u0EAC\u0EBA\u0EBE\u0EBF\u0EC5\u0EC7\u0ECE\u0ECF\u0EDA\u0EDB\u0EE0-\u0EFF\u0F48\u0F6D-\u0F70\u0F98\u0FBD\u0FCD\u0FDB-\u0FFF\u10C6\u10C8-\u10CC\u10CE\u10CF\u1249\u124E\u124F\u1257\u1259\u125E\u125F\u1289\u128E\u128F\u12B1\u12B6\u12B7\u12BF\u12C1\u12C6\u12C7\u12D7\u1311\u1316\u1317\u135B\u135C\u137D-\u137F\u139A-\u139F\u13F5-\u13FF\u169D-\u169F\u16F1-\u16FF\u170D\u1715-\u171F\u1737-\u173F\u1754-\u175F\u176D\u1771\u1774-\u177F\u17DE\u17DF\u17EA-\u17EF\u17FA-\u17FF\u180F\u181A-\u181F\u1878-\u187F\u18AB-\u18AF\u18F6-\u18FF\u191D-\u191F\u192C-\u192F\u193C-\u193F\u1941-\u1943\u196E\u196F\u1975-\u197F\u19AC-\u19AF\u19CA-\u19CF\u19DB-\u19DD\u1A1C\u1A1D\u1A5F\u1A7D\u1A7E\u1A8A-\u1A8F\u1A9A-\u1A9F\u1AAE-\u1AFF\u1B4C-\u1B4F\u1B7D-\u1B7F\u1BF4-\u1BFB\u1C38-\u1C3A\u1C4A-\u1C4C\u1C80-\u1CBF\u1CC8-\u1CCF\u1CF7-\u1CFF\u1DE7-\u1DFB\u1F16\u1F17\u1F1E\u1F1F\u1F46\u1F47\u1F4E\u1F4F\u1F58\u1F5A\u1F5C\u1F5E\u1F7E\u1F7F\u1FB5\u1FC5\u1FD4\u1FD5\u1FDC\u1FF0\u1FF1\u1FF5\u1FFF\u200B-\u200F\u202A-\u202E\u2060-\u206F\u2072\u2073\u208F\u209D-\u209F\u20BB-\u20CF\u20F1-\u20FF\u218A-\u218F\u23F4-\u23FF\u2427-\u243F\u244B-\u245F\u2700\u2B4D-\u2B4F\u2B5A-\u2BFF\u2C2F\u2C5F\u2CF4-\u2CF8\u2D26\u2D28-\u2D2C\u2D2E\u2D2F\u2D68-\u2D6E\u2D71-\u2D7E\u2D97-\u2D9F\u2DA7\u2DAF\u2DB7\u2DBF\u2DC7\u2DCF\u2DD7\u2DDF\u2E3C-\u2E7F\u2E9A\u2EF4-\u2EFF\u2FD6-\u2FEF\u2FFC-\u2FFF\u3040\u3097\u3098\u3100-\u3104\u312E-\u3130\u318F\u31BB-\u31BF\u31E4-\u31EF\u321F\u32FF\u4DB6-\u4DBF\u9FCD-\u9FFF\uA48D-\uA48F\uA4C7-\uA4CF\uA62C-\uA63F\uA698-\uA69E\uA6F8-\uA6FF\uA78F\uA794-\uA79F\uA7AB-\uA7F7\uA82C-\uA82F\uA83A-\uA83F\uA878-\uA87F\uA8C5-\uA8CD\uA8DA-\uA8DF\uA8FC-\uA8FF\uA954-\uA95E\uA97D-\uA97F\uA9CE\uA9DA-\uA9DD\uA9E0-\uA9FF\uAA37-\uAA3F\uAA4E\uAA4F\uAA5A\uAA5B\uAA7C-\uAA7F\uAAC3-\uAADA\uAAF7-\uAB00\uAB07\uAB08\uAB0F\uAB10\uAB17-\uAB1F\uAB27\uAB2F-\uABBF\uABEE\uABEF\uABFA-\uABFF\uD7A4-\uD7AF\uD7C7-\uD7CA\uD7FC-\uF8FF\uFA6E\uFA6F\uFADA-\uFAFF\uFB07-\uFB12\uFB18-\uFB1C\uFB37\uFB3D\uFB3F\uFB42\uFB45\uFBC2-\uFBD2\uFD40-\uFD4F\uFD90\uFD91\uFDC8-\uFDEF\uFDFE\uFDFF\uFE1A-\uFE1F\uFE27-\uFE2F\uFE53\uFE67\uFE6C-\uFE6F\uFE75\uFEFD-\uFF00\uFFBF-\uFFC1\uFFC8\uFFC9\uFFD0\uFFD1\uFFD8\uFFD9\uFFDD-\uFFDF\uFFE7\uFFEF-\uFFFB\uFFFE\uFFFF]/g;
                shortStr = shortStr.replace(re, "");
                decryptedData += shortStr;

                try {
                    decryptedData = JSON.parse(decryptedData);
                }
                catch (e) {
                    decryptedData = {};
                }

                //console.log(JSON.stringify(args), args);
                //console.log(JSON.stringify(decryptedData), decryptedData);

                callback(decryptedData);
            }
        });
    },
    // remove user data from app
    clearUser: function(){
        delete FXAPI.GLOBAL_API_KEY;
        delete FXAPI.userName;
        FXAPI.dataSets = [];
        localStorage.removeItem("GLOBAL_API_KEY");
    },
    // get user sets
    getUserSets: function(callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            FXAPI.db.transaction(function(tx){
                var newQuery = 'SELECT * FROM user_sets_tbl WHERE schema_id="' + DFXAPI.SCHEMA_ID + '" AND email="' + FXAPI.currentUserMail + '"';

                tx.executeSql(newQuery, [], function (tx, res) {
                    var setsList = {};
                    for (var i = 0; i < res.rows.length; i++) {
                        var currentData = res.rows.item(i);

                        setsList[currentData.set_id] = {
                            display_name: currentData.display_name,
                            name: currentData.name,
                            object_id: parseInt(currentData.set_id)
                        };
                    }

                    DFXAPI.dataSets = setsList;
                    callback(setsList);
                });
            }, function(){
                error_callback('Data in local db is corrupted.')
            });
        }
        else {
            var args = {
                function: "widget/schema_sets"
            };
            
            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },

    // get widget
    getWidget : function(id, typeOfData, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            FXAPI.db.transaction(function(tx){
                tx.executeSql('SELECT * FROM `'+ typeOfData + '_tbl` WHERE '+ typeOfData + '_id=' + id, [], function (tx, results) {
                    //var response = (typeOfData == 'query')?  : results.rows.item(0).code;
                    var code = results.rows.item(0);
                    code.object_id =  id;

                    try {
                        code.fields = JSON.parse(code.code);
                    }
                    catch(e) {}

                    callback(code);
                });
            }, function(error){
                error_callback(error.message);
            });
        }
        else {
            var args = {
                function: "widget/" + typeOfData,
                data: {}
            };

            args.data[typeOfData + '_id'] = id;
            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },

    // get query result
    getQueryResult: function(data, callback, error_callback) {
        data.set_id = DFXAPI.activeDataSet;

        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            DFXAPI.db.transaction(function(tx){
                var cmd = 'SELECT * FROM `query_tbl` WHERE query_id=' + data.query;
                tx.executeSql(cmd, [], function (tx, results) {
                    var res = {};
                    try {
                        var temp = JSON.parse(results.rows.item(0).result);

                        var limit = data.limit || '30';
                        var offset = data.offset || 0;

                        var ind = 0;
                        for( var k in temp) {
                            if(ind >= offset &&  ind < offset + limit) {
                                res[k] = temp[k];
                            }
                            ind++;
                        }
                    }
                    catch(e) {}

                    callback(res);
                })
            }, function(error) {
                error_callback(error.message)
            })
        }
        else {
            var args = {
                function: "query",
                data: data
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }

    },
    // get query count
    getQueryCount: function(data, callback, error_callback){
        data.set_id = DFXAPI.activeDataSet;

        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            DFXAPI.db.transaction(function(tx){
                tx.executeSql('SELECT * FROM `query_tbl` WHERE query_id=' + data.query, [], function (tx, results) {
                    var res;
                    try {
                        res = JSON.parse(results.rows.item(0).result);
                    }
                    catch(e) {
                        res = {};
                    }
                    var len = 0;
                    $.each(res, function() { len++; });
                    callback(len);
                })
            }, function(error) {
                error_callback(error.message);
            })
        }
        else {
            var args = {
                function: "query/count",
                data: data
            };

            //console.log(data);
            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },

    // get object
    getObject: function(typeId, objectId, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            DFXAPI.db.transaction(function(tx){
                tx.executeSql('SELECT * FROM `object_type_' + typeId + '` WHERE object_id=' + objectId, [], function (tx, results) {
                    var res = results.rows.item(0);
                    $.each(res, function(i, val) {
                        res[i] = val.toString();
                    });
                    callback(res);
                })
            }, function(error) {
                error_callback(error.message);

            })
        }
        else {
            var args = {
                function: "object",
                data:{
                    set_id:  DFXAPI.activeDataSet,
                    object_id: objectId,
                    object_type_id: typeId
                }
            };

            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },
    // create object
    addObject: function(object, link_with_user, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"addObject":["This method doesn`t works in offline mode."]},"error_data":[]});
            //cordovaAlert('addObject can`t works in offline mode');

        }
        else {
            var data = $.extend({
                set_id: DFXAPI.activeDataSet,
                object_type_id: object.object_type
            }, object);

            delete data.object_type;

            if(link_with_user == '1') {
                data._link_with_user = true;
            }
            var args = {
                function: "object",
                type: "POST",
                data: data
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }


    },
    // update existing object
    updateObject: function(object, link_with_user, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"updateObject":["This method doesn`t works in offline mode."]},"error_data":[]});
            //cordovaAlert('updateObject can`t works in offline mode');

        }
        else {
            var data = $.extend({
                set_id: DFXAPI.activeDataSet,
                object_type_id: object.object_type
            }, object);
            
            delete data.object_type;

            if (link_with_user == '1') {
                data._link_with_user = true;
            }

            var args = {
                function: "object",
                type: "PUT",
                method: "PUT",
                data: data
            };
            
            DFXAPI.generalRequest(args, callback, error_callback);
        }

    },
    // remove object
    removeObject: function(object, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"removeObject":["This method doesn`t works in offline mode."]},"error_data":[]});
            //cordovaAlert('removeObject can`t works in offline mode');
        }
        else {
            var data = $.extend({
                set_id: DFXAPI.activeDataSet,
                object_type_id: object.object_type
            }, object);

            delete data.object_type;

            var args = {
                function: "object",
                type: "DELETE",
                data: data
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },

    // get all links (possible + checked actual)
    getAllLinks: function(objectId, objectTypeId, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            DFXAPI.db.transaction(function(tx){
                var res = [];

                var query = 'SELECT * FROM `link_tbl` WHERE object_1_id=' + objectId + ' AND object_type_1_id=' + objectTypeId +
                    ' UNION ' + 'SELECT * FROM `links_tbl` WHERE object_2_id=' + objectId + ' AND object_type_2_id=' + objectTypeId

                tx.executeSql(query, [], function (tx, results) {
                    $.each(results, function(i, res) {
                        var link = results.rows.item(i);
                        res.push(link);
                    });
                });

                callback(res);
            }, function(error){
                error_callback(error.message);
            });

        }
        else {
            var data = {
                object_id: objectId,
                object_type_id: objectTypeId
            };
            var args = {
                function: "link/all",
                data: data
            };

            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },
    // get possible links
    getPossibleLinks: function(objectTypeId, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            //DFXAPI.db.transaction(function(tx){
            //    tx.executeSql('SELECT * FROM `links_tbl`', [], function (tx, results) {
            //        callback(results.rows.item(0).code);
            //    })
            //})
            error_callback({"errors":{"getPossibleLinks":["Links are not supported in offline mode."]},"error_data":[]});
        }
        else {
            var data = {
                object_type_id: objectTypeId
            };
            var args = {
                function: "link/possible",
                data: data
            };

            DFXAPI.generalRequest(args, callback, error_callback);
        }

    },

    // upload image to object
    uploadImage : function(fileURI, field, objectTypeId, objectId, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            //TODO may be error
            error_callback({"errors":{"uploadImage":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var afterUpload = function(response) {
                callback(response);
            };

            if(!callback) callback = $.noop;
            if(!error_callback) error_callback = $.noop;


            var toHex = function(string) {
                var i, f = 0,
                    a = [];

                string += '';
                f = string.length;

                for (i = 0; i < f; i++) {
                    a[i] = string.charCodeAt(i).toString(16).replace(/^([\da-f])$/, "0$1");
                }

                return a.join('');
            };

            var options = new FileUploadOptions();
            options.fileKey = field;
            options.fileName = fileURI.substr(fileURI.lastIndexOf('/') + 1);
            options.mimeType = "image/jpeg";
            options[field] = "@"+options.fileName;
            options.params = {}; // if we need to send parameters to the server request


            var bf = new Blowfish(DFXAPI.encryptKey);

            options.params = {
                data: toHex(bf.encrypt(JSON.stringify({object_type_id: objectTypeId, object_id: objectId}), undefined)),
                api_key: DFXAPI.API_KEY
            };
            var ft = new FileTransfer();

            ft.upload(fileURI, encodeURI(DFXAPI.BASE_API_URL + "file"), callback, error_callback, options);

        }

    },

    // get subscription
    getSubscription: function(user_id, callback, error_callback){
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getSubscription":["This method doesn`t works in offline mode."]},"error_data":[]});
        }

        else {
            var args = {
                function: "subscription/id",
                data:{
                    user_id: user_id
                }
            };

            DFXAPI.generalRequest(args, callback, error_callback);
        }

    },

    // get roles
    getRoles: function(callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getRoles":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "role/by_user",
                data: {
                    set_id: DFXAPI.activeDataSet
                }
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },
    // create role
    addRole: function(userId, roleId, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"addRole":["This method doesn`t works in offline mode."]},"error_data":[]});

        }
        else {
            var args = {
                function: "role",
                type: 'PUT',
                data: {
                    user_id: userId,
                    role_id: roleId,
                    set_id: DFXAPI.activeDataSet
                }
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },
    // remove role
    removeRoles: function(sfxId, roleId, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"removeRoles":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "role",
                type: 'DELETE',
                data: {
                    set_id: DFXAPI.activeDataSet,
                    sfx_id: sfxId,
                    role_id: roleId
                }
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }

    },

    // ibeacons
    getBeacons: function(object_id, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getBeacons":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "beacons",
                data: {
                    object_id: object_id
                }
            };
            DFXAPI.generalRequest(args, callback, error_callback)
        }
    },

    // get data for local database
    getSynchTree: function(dataset, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getSynchTree":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "sync/map",
                data: {
                    set_id: dataset
                }
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }

    },

    // get data for local database
    getSynchData: function(dataset, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getSynchData":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function: "sync",
                data: {
                    set_id: dataset
                }
            };
            DFXAPI.generalRequest(args, callback, error_callback);
        }
    },

    getExchangeRate : function(amount, from, to, callback, error_callback) {
        if(FXAPI.offlineMode && FXAPI.isPhonegap()) {
            error_callback({"errors":{"getExchangeRate":["This method doesn`t works in offline mode."]},"error_data":[]});
        }
        else {
            var args = {
                function:'widget/exchange_rate',
                data: {
                    amount: amount,
                    from: from,
                    to: to
                }
            };

            DFXAPI.generalRequest(args, callback, error_callback);
        }

    }
};