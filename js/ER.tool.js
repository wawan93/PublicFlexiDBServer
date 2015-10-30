var flexiweb = window.flexiweb;

var ERToolDesigner = function() {
    var designer = this;
    var $stack = $('#designer');
    var $palette = $('#entities');
    var $status = $('#statusbar');
    var container = this.container = document.getElementById('toolContainer');
    var canvas = this.canvas = document.getElementById('Canvas');

    this.entitiesClass = '.entity';
    this.linksClass = '.middle-label';

    this.items = {};
    this.connections = {};

    this.entityWidth = 90;
    this.entityHeight = 44;

    this.containerHeight = container.parentNode.offsetHeight;
    this.containerWidth = container.parentNode.offsetWidth;

    this.states = [];
    this.stateNumber = undefined;

    this.firstStart = true;

    var siteURL = flexiweb.site_url;
    var ajaxURL = siteURL + '/ajax/';

    /* Current method init tool. */

    this.initConnections = function( connections ) {
        $.each(connections, function(connection, data) {
            if(typeof connection == 'undefined')
                return null;

            var str = connection;
            var connectionInfo = str.split('-');
            var item1 = document.getElementById(connectionInfo[0]);
            var item2 = document.getElementById(connectionInfo[1]);

            var relation = parseInt(data.relation);
            var strength = parseInt(data.strength);

            if(relation == 0)
                return null;

            if((item1 == undefined || item1 == null) || (item2 == undefined || item2 == null))
                return null;

            if(designer.checkConnection(item1, item2))
                return null;

            designer.draw(designer.createConnection(item1, item2, relation, strength));
            designer.correctionCoordinates(item1, item2);
        });
    };

    this.initPaletteItems = function() {
        $palette.find(designer.entitiesClass).draggable({
            connectWith: $stack,
            revert: 'invalid',
            helper: 'clone'
        });
    };
    this.prepareData = function(ER) {
        if(ER.hasOwnProperty('types_free')) {
            $.each(ER.types_free, function(id, data) {
                data.id = 'er_' + id;
                data.classes = 'in-palette';
                if(data.system)
                    data.classes += ' system';


                var $currentEntity = ich['entity'](data);
                $palette.append($currentEntity);
            });
        }
        if(ER.hasOwnProperty('types_in_use')) {
            $.each(ER.types_in_use, function(id, data) {
                data.id = 'er_' + id;
                data.classes = '';
                if(data.system)
                    data.classes += ' system';

                var $currentEntity = ich['entity'](data);

                var x = data.x;
                var y = data.y;
                if(x < 0) x = 0;
                if(y < 0) y = 0;

                $currentEntity.css({ left: x + 'px', top: y + 'px', position: 'absolute' });
                $stack.append($currentEntity);
            });
        }
        designer.initConnections(ER.connections);
    };

    this.initControls = function() {
        designer.connectionCreateInit();
        $('#saveDiagram').click(function(){
            designer.saveDiagram();
        });
        $('#system-elems').change(function() {

            var $items = $palette.find(designer.entitiesClass);

            $.each($items, function() {
                var $this = $(this);

                if($this.hasClass('system') && $this.hasClass('red')) {
                    $this.hide();
                }
                else {
                    $this.addClass('visible');
                }
            });
        }).trigger('change');
        $('#zoomControl').change(function(){
            var zoom = $("#zoomControl option:selected").val();
            var parentWidth = designer.containerWidth;
            var parentHeight = designer.containerHeight;//container.parentNode.offsetHeight - 40;

            if(zoom != 1) {
                var width = parentWidth / zoom;
                var left = - width * (1 - zoom) / 2;
                var top = - container.offsetHeight * (1 - zoom) / 2;

                container.style.width =  width + 'px';
                container.style.top =  top + 'px';
                container.style.left =  left + 'px';


                //var headerHeight = $(container).find('.header').height();
                //var footerHeight = $(container).find('.footer').height();
                container.parentNode.style.height = parentHeight * zoom + 'px';
                //container.parentNode.style.height = designer.canvas.style.height + 20;
            }
            else {
                container.style.top = '';
                container.style.left = '';
                container.style.width =  '';
                //container.parentNode.style.height = designer.canvas.style.height + 20;
                container.parentNode.style.height = '';
            }

            $(container).css('transform', 'scale(' + zoom + ',' + zoom + ')');
            //$(container).css('zoom',  zoom + ')');
            $(window).trigger('resize');

        });
        $('#undo, #redo').bind('click', function() {
            var stateNumber = designer.stateNumber;
            var states = designer.states;

            if(states.length == 1)
                return;

            if(typeof stateNumber === 'undefined') {
                stateNumber = states.length - 1;
            }

            if(this.id == 'undo') {
                if(stateNumber < 1)
                    return;

                stateNumber--;
            }

            if(this.id == 'redo') {
                if(stateNumber > states.length - 2)
                    return;

                stateNumber++;
            }

            designer.stateNumber = stateNumber;
            designer.printTypePosition();

            designer.init(states[stateNumber]);
            designer.saveDiagram('temp');

            $(window).trigger('resize');

        });
        $('#revert').bind('click', function() {
            var timeout = flexiweb.loaderShow();
            flexiweb.callFunction.removeTempER(function() {
                flexiweb.callFunction.getSchemaER(function(ER) {
                    designer.types = $.extend({}, ER.types_in_use, ER.types_free);
                    designer.init(ER);
                    designer.states.length = 0;
                    designer.states.push($.extend({}, ER));
                    designer.saveDiagram();
                    flexiweb.loaderHide(timeout);
                })
            });
        });
    };

    /* Current method init draggable for stack items. */
    this.initStackItems = function() {
        var zoom = $('#zoomControl').val()
        function dragFix(event, ui) {
            var percent = $('#zoomControl').val();

            var changeLeft = ui.position.left - ui.originalPosition.left,
                leftMax = designer.xMax - ui.originalPosition.left - designer.entityWidth,
                newLeft = ui.originalPosition.left + changeLeft / percent;

            var changeTop = ui.position.top - ui.originalPosition.top,
                topMax = designer.yMax - ui.originalPosition.top - designer.entityHeight - 15,
                newTop = ui.originalPosition.top + changeTop / percent;

            //console.log('LEFT :: CHANGE=' + changeLeft + ' :: NEW =' + newLeft + ' :: MAX = ' + leftMax);
            //console.log('TOP :: CHANGE=' + changeTop + ' :: NEW =' + newTop + ' :: MAX = ' + topMax);

            var left = newLeft;
            var top = newTop;

            if(newLeft < 0) { left = 0; }
            if(newLeft > leftMax) { left = leftMax; }

            if(newTop < 0) { top = 0; }
            if(newTop > topMax) { top = topMax; }

            ui.position.left = left;
            ui.position.top = top;
        }

        function startFix(event, ui) {
            ui.position.left = 0;
            ui.position.top = 0;
            var element = $(this);
        }

        $stack.find(designer.entitiesClass).draggable({
            //containment: $stack,
            //containment: [$stack.offset().left, $stack.offset().top, ($stack.offset().left + 400) * $('#zoomControl').val(), ($stack.offset().top + 400) * $('#zoomControl').val()],
            helper : 'clone',
            revert: 'none',
            scroll: false,
            connectWith: $stack,

            drag: function ( event, ui ) {
                dragFix(event, ui);
            },

            stop: function ( event, ui ) {
                var $helper = $(ui.helper);
                var $this = $(this);

                if(!$helper.hasClass('in-palette')) {
                    $this.css({top: ui.position.top, left: ui.position.left});
                    $helper.removeClass('in-palette');
                }
                designer.dragCallback(this.id);
                designer.changeStateCallback();
            },
            start: startFix
        });
    };
    /* Current method inits designer. */
    this.initDesigner = function() {
        window.onresize = function() {
            designer.collectItems();

            var items = designer.items;
            var xMax = 0;
            var yMax = 0;

            for( var i in items ) {
                var item = document.getElementById(i);
                xMax = Math.max(xMax, item.offsetLeft);
                yMax = Math.max(yMax, item.offsetTop);
            }


            var width = Math.max(container.offsetWidth, xMax);
            var height = Math.max(container.offsetHeight, yMax);

            console.log(xMax, yMax);

            designer.xMax = width;
            designer.yMax = height;

            console.log(width, height);

            if(xMax > container.offsetWidth) {
                width += designer.entityWidth;
                designer.xMax = width;
                $('canvas').css('width', width);
                $('canvas, #designer').width(width);
            }
            else {
                $('canvas').css('width', '100%');
            }

            designer.initStackItems();
            //console.log(container.offsetHeight - 40, yMax + 44, height);

            //$('canvas').width(width);
            //$('canvas').height(height);
            //$stack.width(width);

            var connectionsList = [],
                connections = designer.connections;

            for (var connection in connections) {
                var currentConnection = connections[connection];
                designer.correctionCoordinates(currentConnection.startItem, currentConnection.endItem);
            }

            designer.initConnections (connectionsList);
        };
        $(window).trigger('resize');

        $stack.droppable({
            accept: designer.entitiesClass,
            drop: function(event, ui) {
                $(ui.draggable).css('position','absolute')
                    .appendTo($stack)
                    .offset($(ui.helper).offset())
                    .draggable('option', 'containment', 'parent');

                designer.initViewType();
                designer.initStackItems();

            },
            stop: function(event, ui) {
                designer.changeStateCallback();
            }
        });
    };

    /* Current method return designer width. */
    this.getWidth = function() {
        return canvas.offsetWidth;
    };
    /* Current method returns designer height. */
    this.getHeight = function() {
        return canvas.offsetHeight;
    };

    /* Current method draw connection. */
    this.drawRegularConnection = function($canvas, connection, startPoint, endPoint, startSide) {
        var xStart = startPoint.x, yStart = startPoint.y,
            xStop = endPoint.x, yStop = endPoint.y,
            Ax, Ay, Bx, By;

        var direction = startSide;
        var xSpace, ySpace;
        var xLabelOffset = 0, yLabelOffset = 0;

        xSpace = Math.abs(xStart - xStop);
        ySpace = Math.abs(yStart - yStop);

        switch(direction) {
            case 'top' : {
                Ax = xStart;
                Ay = yStart - ySpace/2;

                Bx = xStop;
                By = Ay;

                xLabelOffset = (Ax <= Bx)? xSpace/2 : - xSpace/2;
                break;
            }
            case 'bottom' : {
                Ax = xStart;
                Ay = yStart + ySpace/2;

                Bx = xStop;
                By = Ay;

                xLabelOffset = (Ax <= Bx)? xSpace/2 : - xSpace/2;
                break;
            }
            case 'left' : {
                Ax = xStart - xSpace/2 ;
                Ay = yStart;

                Bx = Ax;
                By = yStop;

                yLabelOffset = (Ay <= By)? ySpace/2 : - ySpace/2;

                break
            }
            case 'right' : {
                Ax = xStart + xSpace/2 ;
                Ay = yStart;

                Bx = Ax;
                By = yStop;

                yLabelOffset = (Ay <= By)? ySpace/2 : - ySpace/2;
                break;
            }
        }

        designer.connections[connection]['yLabel'] = Math.round(Ay + yLabelOffset);
        designer.connections[connection]['xLabel'] = Math.round(Ax + xLabelOffset);

        var aPoint = {x : Math.round(Ax), y : Math.round(Ay)},
            bPoint = {x : Math.round(Bx), y : Math.round(By)};

        var strength = designer.connections[connection].strength;

        designer.drawLine($canvas, startPoint, aPoint, strength);
        designer.drawLine($canvas, aPoint, bPoint, strength);
        designer.drawLine($canvas, bPoint, endPoint, strength);

        //console.log(designer.connections[connection]['strength']);

        designer.drawConnectionType($canvas, startPoint, endPoint, designer.connections[connection].relation)
    };
    /* Current method draw @item connection with himself on @$canvas. Connection will have @connectionId as id. */
    this.drawSelfConnection = function($canvas, connectionId, item) {
        var $item = $(item);
        var startPoint = {}, endPoint = {};

        startPoint.x = $item.getXCoordinate() + $item.outerWidth() - 10;
        startPoint.y = $item.getYCoordinate();

        endPoint.x = $item.getXCoordinate() + $item.outerWidth();
        endPoint.y = $item.getYCoordinate() + 10;

        var pointA = {x: startPoint.x, y: startPoint.y - 10},
            pointB = {x: startPoint.x + 20, y: startPoint.y - 10},
            pointC = {x: endPoint.x + 10, y: endPoint.y};

        designer.connections[connectionId].yLabel = pointB.y;
        designer.connections[connectionId].xLabel = pointB.x;

//            console.log(startPoint, pointA, pointB, pointC, endPoint);
        designer.drawLine($canvas, startPoint, pointA);
        designer.drawLine($canvas, pointA, pointB);
        designer.drawLine($canvas, pointB, pointC);
        designer.drawLine($canvas, pointC, endPoint);

        designer.drawConnectionType($canvas, startPoint, endPoint, designer.connections[connectionId].type)
    };
    /* Current method draw label on canvas depends on type and strength. */
    this.drawLabel = function(connectionId) {
        var connection = designer.connections[connectionId];
        var startItem = connection.startItem;
        var endItem = connection.endItem;
        var relation = connection.relation;
        var connectionStrength = connection.strength;
        var labelText;

        var $label = designer.getElement(startItem, endItem, '#link-');

        switch( relation ) {
            case 1: labelText = '1-1'; break;
            case 2: labelText = '1-N'; break;
            case 3: labelText = 'N-1'; break;
            case 4: labelText = 'N-N'; break;
        }
        //$label = $('#link-' + connectionId + '-' + relation + '-' + connectionStrength);
        if (connection.label == null) {
            var labelId = 'link-' + connectionId; //+ '-' + relation + '-' + connectionStrength;

            $label = $('<div>')
                .addClass('button middle-label')
                .attr('data-relation', relation)
                .attr('data-strength', connectionStrength)
                .attr('data-start', startItem.id)
                .attr('data-end', endItem.id)
                .text(labelText)
                .attr('id', labelId);

            $stack.append($label);
            connection.label = $label.get(0);
        }

        var $label = $(connection.label);

        $label.css({
            'top' : connection.yLabel - $label.outerHeight()/2 + 'px',
            'left' : connection.xLabel - $label.outerWidth()/2 + 'px'
        });

        if(connection.strength)
            $label.addClass('red');

        designer.initEditLink();
    };
    /* Current method draws line from @startPoint to @stopPoint with @strength on @canvas. */
    this.drawLine = function(currentCanvas, startPoint, stopPoint, strength ) {
        var $canvas = $(currentCanvas);
        var drawing = $canvas.get(0).getContext('2d');

        if(strength)
            $canvas.addClass('red');

        drawing.beginPath();
        drawing.strokeStyle = strength ? 'red' : "black";
        drawing.moveTo(startPoint.x, startPoint.y);
        drawing.lineTo(stopPoint.x, stopPoint.y);
        drawing.stroke();
        drawing.closePath();
    };
    /* Current method draws special figure depends on @type. */
    this.drawConnectionType = function(currentCanvas, startPoint, endPoint, relation) {
        var xStop =  endPoint.x,
            yStop =  endPoint.y,
            xStart = startPoint.x,
            yStart = startPoint.y;
        
        var drw = currentCanvas.get(0).getContext('2d');

        drw.beginPath();

        var drawRhomb = function(x, y, size) {
            var size = size || 5;
            drw.moveTo(x - size, y);
            drw.lineTo(x, y + size);

            drw.moveTo(x, y + size);
            drw.lineTo(x + size, y);

            drw.moveTo(x + size, y);
            drw.lineTo(x, y - size);

            drw.moveTo(x, y - size);
            drw.lineTo(x - size, y);
        };
        

        if(relation == 4) {
            var xStart =  startPoint.x,
                yStart =  startPoint.y;

            drawRhomb(xStop, yStop);
            drawRhomb(xStart, yStart);

            drw.stroke();
            drw.closePath();
            drw.beginPath();
        }
        else if(relation == 3) {
            drawRhomb(xStart, yStart);
            //drw.arc(, yStart, 6, 0, 2 * Math.PI, false);
        }
        else if(relation == 2) {
            drawRhomb(xStop, yStop);
            //drw.arc(xStop, yStop, 6, 0, 2 * Math.PI, false);
        }
        drw.stroke();
    };

    /* Current method draws connection with @connectionId: find start and end points, then draw connection and label. */
    this.draw = function(connectionId) {
        var startItem = designer.connections[connectionId].startItem;
        var endItem = designer.connections[connectionId].endItem;
        var $canvas = designer.getCanvas(startItem, endItem);

        if(startItem == endItem) {
            designer.drawSelfConnection($canvas, connectionId, startItem);
        }
        else {
            var startSide = designer.getDirectionBetweenItems(startItem, endItem);
            var endSide = designer.getDirectionBetweenItems(endItem, startItem);
            var startPoint = designer.getPoint(startItem, connectionId, startSide);
            var endPoint = designer.getPoint(endItem, connectionId, endSide);

            designer.drawRegularConnection($canvas, connectionId, startPoint, endPoint, startSide);
        }

        designer.drawLabel(connectionId);
        designer.collectItems();
    };
    /* Current method redraws connections which contains  @item1 and @item2. */
    this.correctionCoordinates = function(item1, item2) {
        var firstSide = designer.getDirectionBetweenItems(item1, item2);
        var secondSide = designer.getDirectionBetweenItems(item2, item1);
        var firstItemLinkedConnections = designer.items[item1.id][firstSide];
        var secondItemLinkedConnections = designer.items[item2.id][secondSide];

        if(item1 == item2) {
            var connection = item1.id + '-' + item1.id;
            designer.eraseConnection(connection);
            designer.draw(connection);
        }
        else {
            $.each(firstItemLinkedConnections, function(item, innerConnection) {
                designer.eraseConnection(innerConnection);
                designer.draw(innerConnection);
            });
            $.each(secondItemLinkedConnections, function(item, innerConnection) {
                designer.eraseConnection(innerConnection);
                designer.draw(innerConnection);
            });
        }


    };

    /* Current method find and remove canvas which contains @connection. */
    this.eraseConnection = function(connectionId) {
        var connectedItems = connectionId.split('-');
        var $drawing1 = $('#' + connectionId);
        var $drawing2 = $('#' + connectedItems[1] + '-' + connectedItems[0]);

        $drawing1.remove();
        $drawing2.remove();

    };
    /* Current method creates connection and push data in it. Returns connection id. */
    this.createConnection = function(startItem, endItem, relation, strength) {
        var id = startItem.id + '-' + endItem.id;
        designer.connections[id] = {
            'startItem' : startItem,
            'endItem' : endItem,
            'label' : null,
            'relation' : relation,
            'strength' : strength
        };
        return id;
    };
    /* Current method removes connection with @connectionId. */
    this.removeConnection = function(connectionId) {
        designer.eraseConnection(connectionId);
        $(designer.connections[connectionId].label).remove();
        delete designer.connections[connectionId];
        designer.collectItems();
    };
    /* Current method creates connection and push data in it. Returns connection id. */
    this.changeConnectionType = function(id1, id2, newType, newStrength) {
        var connectionId = id1 + '-' + id2;

        designer.removeConnection(connectionId);

        //document.get
        var item1 = document.getElementById(id1);
        var item2 = document.getElementById(id2);

        designer.createConnection(item1, item2, newType, newStrength);
        designer.draw(connectionId);
        designer.correctionCoordinates(item1, item2);
    };

    /* Current method inits drag callback. */
    this.dragCallback = function( draggingItem ) {
        var itemId = draggingItem;
        var currentItemInfo = designer.items[itemId];
        if(getObjectSize(currentItemInfo) > 0) {
            $.each(currentItemInfo, function(side, linkedItems) {
                if(side == 'self') {
                    var selfConnection = itemId + '-' + itemId;
                    designer.eraseConnection(selfConnection);
                    designer.draw(selfConnection);
                }
                else {
                    $.each(linkedItems, function(linkedItem, connection) {
                        designer.eraseConnection(connection);
                        designer.draw(connection);

                        var innerObject = designer.items[linkedItem];
                        if(getObjectSize(innerObject) > 0) {
                            $.each(innerObject, function(innerSide, innerLinkedItems) {
                                $.each(innerLinkedItems, function(innerLinkedItem, innerConnection) {
                                    designer.eraseConnection(innerConnection);
                                    designer.draw(innerConnection);
                                });
                            });
                        }
                    });
                }

            });
        }
    };

    /* Current method init event listeners for create new connections. */
    this.connectionCreateInit = function() {
        $('#links .link_create').mouseup(function(){
            var $itemsInStack = $stack.find('.entity');

            var initEscapeListener = function( connectedItems ) {
                $(document).unbind('keyup').bind('keyup', function(e) {
                    if (e.keyCode == 27) {
                        $(document).unbind('keyup');
                        $itemsInStack.unbind('click');
                        $itemsInStack.draggable('enable');

                        if(typeof connectedItems !== 'undefined')
                            $(connectedItems).css('background-color','');

                        return;
                    }
                });
            }

            var linkType = parseInt(this.dataset.relation);
            var linkStrength = 0;

            initEscapeListener();
            $itemsInStack.draggable('disable');

            $status.empty().append('Please, select first element.');
            $itemsInStack.unbind('click').bind('click', function() {
                var item1 = this;
                var connectedItems = designer.getConnectedItems(item1);
                $(connectedItems).css('background-color','red');
                item1.style.backgroundColor = '#F5AD27';

                $status.empty().append('Please, select second element.');
                initEscapeListener(connectedItems);
                $itemsInStack.unbind('click').bind('click', function() {
                    var item2 = this;
                    item1.style.backgroundColor = '';

                    if(designer.checkConnection(item1, item2)) {
                        $status.empty();
                        $status.append('Link is already exists!');
                        alert('Link is already exist!');
                        $itemsInStack.unbind('click');
                        $itemsInStack.draggable('enable');
                        $(connectedItems).css('background-color','');
                        return null;
                    }
                    else {
                        $(connectedItems).css('background-color','');
                        var connection = designer.createConnection(item1, item2, linkType, linkStrength);
                        designer.draw(connection);
                        designer.correctionCoordinates(item1, item2);
                        $status.empty().append('Double click to view/edit');
                        $itemsInStack.draggable('enable');
                        $itemsInStack.unbind('click');
                        designer.changeStateCallback();
                    }
                })
            })
        });
    };

    /* Current method collect items. */
    this.collectItems = function() {
        designer.items = {};
        var items = designer.items;
        if(getObjectSize(designer.connections) > 0) {
            $.each(designer.connections, function(key, current) {
                var startId = current.startItem.id;
                var stopId = current.endItem.id;

                if(startId == stopId) {
                    if(typeof items[startId] === 'undefined')
                        items[startId] = {};

                    //items[startId].self = key;
                    items[startId].self = true;
                }

                else {
                    var direction = designer.getDirectionBetweenItems(current.startItem,  current.endItem);
                    var revertDirection = designer.getDirectionBetweenItems(current.endItem, current.startItem);

                    if(typeof items[startId] === 'undefined')
                        items[startId] = {};

                    if(typeof items[startId][direction] === 'undefined')
                        items[startId][direction] = {};

                    items[startId][direction][stopId] = key;

                    if(typeof items[stopId] === 'undefined')
                        items[stopId] = {};

                    if(typeof items[stopId][revertDirection] === 'undefined')
                        items[stopId][revertDirection] = {};

                    items[stopId][revertDirection][startId] = key;
                }

            })
        }
    };

    /* Current method returns point with coordinates for new connection. */
    this.getPoint = function(item, connection, side) {
        var width = $(item).width(),
            height = $(item).height(),
            itemId = item.id,
            itemCoordinates = $(item).getCoordinates(),
            itemsOnSide,
            newPoint = {
                x : itemCoordinates.x,
                y : itemCoordinates.y
            };

        if (designer.items && designer.items[itemId] && designer.items[itemId][side]) {
            itemsOnSide = designer.items[itemId][side];

            var pointsNumber = getObjectSize(itemsOnSide),
                sideLength = ((side == 'top') || (side == 'bottom'))? width : height,
                step = (sideLength / (pointsNumber + 1)),
                itemsWithCoordinates = {},
                targetId,
                position;

            if($(designer.connections[connection]['startItem']).attr('id') == itemId) {
                targetId = $(designer.connections[connection]['endItem']).attr('id');
            }
            else {
                targetId = $(designer.connections[connection]['startItem']).attr('id');
            }

            if(getObjectSize(itemsOnSide) > 0) {
                $.each(itemsOnSide, function(linkedItem, connection) {
                    if((side == 'top') || (side == 'bottom')) {
                        itemsWithCoordinates[linkedItem] = $('#' + linkedItem).getXCoordinate();
                    }
                    else {
                        itemsWithCoordinates[linkedItem] = $('#' + linkedItem).getYCoordinate();
                    }
                });
            }

            $.each(sortObject(itemsWithCoordinates), function(pos, object) {
                if(object['key'] == targetId) {
                    position = pos + 1;
                }
            });

            var offset = step * position;
            switch(side) {
                case 'top' :
                    newPoint.x += offset;
                    break;
                case 'bottom' :
                    newPoint.y += height;
                    newPoint.x += offset;
                    break;

                case 'left' :
                    newPoint.y += offset;
                    break;

                case 'right' :
                    newPoint.x += width;
                    newPoint.y += offset;
                    break;
            }
        }
        else {
            switch(side) {
                case 'top' :
                    newPoint.x += width/2;
                    break;
                case 'bottom' :
                    newPoint.x += width/2;
                    newPoint.y += height;
                    break;

                case 'left' :
                    newPoint.y += height/2;
                    break;
                case 'right' :
                    newPoint.y += height/2;
                    newPoint.x += width;
                    break
            }
        }
        newPoint = {
            x: Math.round(newPoint.x),
            y: Math.round(newPoint.y)
        }

        return newPoint;
    };
    /* Current method returns vector direction from (0,0) to (x,y). */
    this.getDirection = function(x, y) {
        if((y < x) && (y < -x)) {
            return 'top';
        }
        else if((y < x) && (y > -x)) {
            return 'right';
        }
        else if((y > x) && (y < -x)) {
            return 'left';
        }
        else if((y > x) && (y > -x)) {
            return 'bottom';
        }
    };
    /* Current method returns vector direction between two items. */
    this.getDirectionBetweenItems = function(startItem, stopItem) {
        return designer.getDirection($(stopItem).getXCoordinate() - $(startItem).getXCoordinate(),
            $(stopItem).getYCoordinate() - $(startItem).getYCoordinate());
    };

    /* Current method returns vector direction between two items. */
    this.getElement = function(startItem, stopItem, prefix) {
        var item1 = document.getElementById(prefix + startItem.id + '-' + stopItem.id);
        var item2 = document.getElementById(prefix + stopItem.id + '-' + startItem.id);

        if(item1 != null && item1 != undefined)
            return item1;

        else if(item2 != null && item2 != undefined)
            return item2;

        else return null;
    };
    /* Current method returns canvas element. Create if it isn't exists. */
    this.getCanvas = function(startItem, stopItem) {
        var $existingCanvas = designer.getElement(startItem, stopItem, '#'),
            $newCanvas,
            id = $(startItem).attr('id') + '-' + $(stopItem).attr('id');

        if(!$existingCanvas) {
            $('<canvas>')
                .attr({
                    id : id,
                    class : 'connection',
                    width : designer.getWidth(),
                    height : designer.getHeight(), //$(canvas).height(),
                    'data-start' : $(startItem).attr('id'),
                    'data-stop' : $(stopItem).attr('id')
                })
                .css({
                    position : 'absolute',
                    top : 0
                })
                .insertBefore($stack);

            $newCanvas = $('#' + id);

        }
        else {
            $newCanvas = $existingCanvas;
        }

        return $newCanvas;
    };

    /* Current method returns all items, which has connection with current. */
    this.getConnectedItems = function( item ) {
        var $item = $(item);
        var existingConnections = designer.getConnections($item);
        var res = [];

        for(var i = 0; i < existingConnections.length ; i++) {
            var connection = designer.connections[existingConnections[i]];

            if(item != connection.startItem) res.push(connection.startItem);
            if(item != connection.endItem) res.push(connection.endItem);
            if(connection.startItem == connection.endItem) res.push(item)
        }

        return res;
    };
    /* Current method returns all connections, which start or stop in @item. */
    this.getConnections = function ( item ) {
        var itemId = $(item).attr('id'),
            res = [],
            items = designer.items[itemId];

        if(items) {
            $.each(items, function(item, sides) {
                if(sides) {
                    $.each(sides, function(endItemId, connection) {
                        res.push(connection);
                    });
                }
            });
        }

        return res;
    };
    /* Current method returns true if @item1 and @item2 has connection and false if don't. */
    this.checkConnection = function(item1, item2) {
        var id1 = item1.id + '-' + item2.id;
        var id2 = item2.id + '-' + item1.id;
        return (typeof designer.connections[id1] !== 'undefined' || typeof designer.connections[id2] !== 'undefined');
    };

    /* Current method adds event listener on click by type. */
    this.initViewType = function() {
        $(designer.entitiesClass).unbind('dblclick').dblclick(function(){
            var type_id = this.id;
            var links = [];
            $('label','#designer').each(function(index, element) {
                var cur_id = element.id;
                var types = cur_id.split('-');
                if(types[0] == type_id || types[1] == type_id )
                    links.push(cur_id);
            });
            initDialogWindow({
                url: ajaxURL + 'er_view_type.php?object_type_id=' + type_id + '&links='+JSON.stringify(links),
                title: "Type Details",
                iframe: false
            });
        });
    };
    /* Current method adds event listener on click by connection label. */
    this.initEditLink = function() {
        $stack.find(designer.linksClass).unbind('dblclick').dblclick(function(){
            var id = this.id.replace("link-", "");

            var editLinkCallback = function($dialogForm) {
                //var $dialogForm = $("#dialog-form");

                $dialogForm.find('input[value="Update"]').click(function() {
                    var connection = designer.connections[id];
                    var relation = parseInt($('#relation').val());
                    var strength = parseInt($('#strength').val());
                    designer.changeConnectionType(connection.startItem.id, connection.endItem.id, relation, strength);
                    designer.changeStateCallback();
                    $dialogForm.html('').dialog("destroy");
                });

                $dialogForm.find('input[value="Delete"]').click(function() {
                    if(confirm('Are you sure that you want to remove that connection?')) {
                        designer.removeConnection(id);
                        designer.changeStateCallback();
                        $dialogForm.html('').dialog("destroy");
                    }

                });

                $dialogForm.find('input[value="Close"]').click(function() {
                    $dialogForm.html('').dialog("destroy");
                });
            }
            initDialogWindow({
                url: ajaxURL + 'er_edit_link.php?data=' + this.id + '-' + this.dataset.relation + '-' + this.dataset.strength,
                width: 300,
                title: "Edit Link",
                iframe: false
            }, editLinkCallback);
        });
    };
    /* Current method saves diagramm. */

    this.changeStateCallback = function() {
        designer.saveDiagram('temp');
        designer.states.push(designer.collectData());

        if(typeof designer.stateNumber !== 'undefined') {
            designer.states = designer.states.slice(0, designer.stateNumber + 1);
            designer.states.push(designer.collectData());
            designer.stateNumber = undefined;
        }

        designer.printTypePosition();
    };

    this.collectData = function() {
        var currentState = {};
        var types_in_use = {};
        var types_free = $.extend({}, designer.types);

        $('.entity', '#designer').each(function() {
            var id = this.id;
            if(typeof id !== 'undefined' && id != null && id != '') {
                id = id.replace('er_', '');
                types_in_use[id] = $.extend({}, types_free[id]);
                types_in_use[id].x = this.offsetLeft;
                types_in_use[id].y = this.offsetTop;
                delete types_free[id];
            }
        });

        currentState.connections = $.extend({}, designer.connections);
        currentState.types_in_use = $.extend({}, types_in_use);
        currentState.types_free = $.extend({}, types_free);
        return $.extend({}, currentState)
    };

    this.saveDiagram = function (arg) {
        var scriptName = 'er_save_diagram.php';
        var indicator = '';

        if( arg == 'temp') {
            scriptName = 'er_temp.php';
            indicator = '<sup>*</sup>';
        }

        var $container = $('<div>').css('width','100%');
        var $img = $('<img>').attr('src', siteURL + 'images/loader-small.gif');

        $("#statusbar").empty().append($container.append($img).append('Diagram saving...'));

        var positions = [];
        var connections = [];

        $('.entity', '#designer').each(function(index, element) {
            positions.push([$(element).attr('id'),$(element).css('left'),$(element).css('top')]);
        });

        for (var i in designer.connections) {
            if(designer.connections.hasOwnProperty(i)) {
                var connection = designer.connections[i];
                var str = i + '-' + connection.relation + '-' + connection.strength;
                connections.push(str);
            }
        }

        $.ajax({
            url: ajaxURL + scriptName,
            type: "POST",
            data: 'connections=' + JSON.stringify(connections) + '&positions=' + JSON.stringify(positions),
            success: function(html) {
                $("#statusbar").html(html);
                $("#er-status").html(indicator);
            }
        });
    };

    this.init = function( data, callback ) {
        if(typeof data === 'undefined' || data == null)
            return;

        if(!callback) callback = $.noop;

        $stack.empty();
        $palette.empty();
        //console.log($stack, $('canvas').not('#Canvas'));
        $('canvas').not('#Canvas').remove();

        designer.connections = {};
        designer.items = {};

        designer.prepareData(data);
        designer.initPaletteItems();

        if(designer.firstStart) {
            designer.initDesigner();
            designer.initControls();
        }
        else {
            $('#system-elems').trigger('change');
        }

        designer.initStackItems();
        designer.initViewType();
        designer.dragCallback();
        designer.firstStart = false;
        callback();
    };

    this.printTypePosition = function(type, field) {
        var list = [];
        var typeName;
        type = type || 22;
        field = field || 'y';
        if(typeof designer.states !== 'undefined') {
            $.each(designer.states , function() {
                if(typeof this.types_in_use !== 'undefined')
                    if(typeof this.types_in_use[type] !== 'undefined') {
                        list.push(this.types_in_use[type].y) ;
                        typeName = this.types_in_use[type].display_name;
                    }
            });
        }
        //console.log('TYPE `' + typeName + '` HAS `' + field + '` COORD = [ ' + list.toString() + ' ]');

        var number = designer.stateNumber;
        if(typeof number !== 'undefined')
            console.log('actual position in legend is ' + number + ', has value =' + list[number])
    };


    var timeout = flexiweb.loaderShow();
    flexiweb.callFunction.getSchemaER(function(ER) {
        console.log(ER);
        designer.types = $.extend({}, ER.types_in_use, ER.types_free);
        designer.states.push($.extend({}, ER));
        designer.init(ER, function() {
            flexiweb.loaderHide(timeout);
        });
        $(window).trigger('resize');
    });
};
