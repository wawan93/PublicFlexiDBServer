var Chart = function( widgetData ) {
    var namesForY = widgetData.y;
    var nameForX = widgetData.x;
    var queryData = widgetData.queryData;
    var chartType = widgetData.chartType;

    var divId = widgetData.containerId || 'svg';

    var screenMinSide = Math.min(document.body.offsetHeight, document.body.offsetWidth);
    var containerWidth = widgetData.containerWidth || document.getElementById(divId).offsetWidth;
    var containerHeight =  widgetData.containerHeight  || Math.floor(0.7 * screenMinSide);

    var thisChart = this;
    var withoutAnimation = thisChart.withoutAnimation = widgetData.withoutAnimation || false;
    var duration = thisChart.duration = widgetData.duration || 500;

    thisChart.zoom = (typeof widgetData.zoom !== 'undefined') ? widgetData.zoom : true;
    thisChart.withLegend = (typeof widgetData.withLegend !== 'undefined') ? widgetData.withLegend : true;

    thisChart.isVisible = false;
    thisChart.isReady = false;

    this.renderData = function() {
        try { queryData = JSON.parse(queryData); }
        catch (e) {}

        var sortable  = [];


        var counter = 0;
        for (var i in queryData) {
            if(queryData.hasOwnProperty(i)) {
                var curVal = queryData[i][nameForX];

                var obj = {
                    alias: curVal
                };

                if(!$.isNumeric(curVal)) {
                    obj.value = counter;
                    counter++;
                } else
                    obj.value = queryData[i][nameForX];


                queryData[i]["custom_"+nameForX] = obj;
                //console.log(queryData[i]["custom_"+nameForX].value);
                sortable.push(parseInt(obj.value));
            }
        }
        sortable.sort(function(a,b){return a-b;});

        var data = sortable.map(function(cur) {
            for (var k in queryData) {
                if(queryData[k]["custom_"+nameForX].value == cur) {
                    var currentPoint = queryData[k];
                    delete queryData[k];
                    return currentPoint;
                }
            }
        });

        //console.log(sortable, data, queryData);

        var color = d3.scale.ordinal().range(["#45aede", "#f43735" , "#a6ce3a", "#b242dc", "#dc42af", "#f1d74a", "#e78935", "#35e7b3", "#605e5f", "#49e0be"]);
        var stack = d3.layout.stack().values(function (d) { return d.values; });

        var dataValues = stack(namesForY.map(function (name) {
            return {
                name: name,
                values: data.map(function (d) {
                    //console.log(d, name, d[name]);
                    return {
                        coord: d["custom_"+nameForX].value,
                        coordAlias: d["custom_"+nameForX].alias,
                        y: +d[name]
                    }
                })
            }
        }));

        var maxStackedValue = d3.max(data.map(function (d) { return d3.sum(namesForY.map(function (v) { return d[v]}))}));

        data.forEach(function (d) {
            var y0 = 0;
            d.name = d[nameForX]
            d.dataValues = namesForY.map(function (name) {
                return {name: name, y0: y0, y: y0 += +d[name], coord: +d["custom_"+nameForX].value, coordAlias: d["custom_"+nameForX].alias};
            });
        });

        thisChart.isNumericXAxis = $.isNumeric(data[0]["custom_"+nameForX].alias);
        thisChart.color = color;
        thisChart.data = data;
        thisChart.dataValues = dataValues;
        thisChart.maxStackedValue = maxStackedValue;
    };
    this.initAxisAndContext = function() {

        /*
         * 320x480 -> 70/230/280
         * 480x800 -> 70/280/330
         * 640x960 -> 80/320/380
         * 640x1136 -> 80/320/380
         * 720x1280 -> 100/430/500
         * 768x1024 -> 100/430/500
         * 900x1400 -> 120/510/600
         * 1536x2048 -> 240/800/1000
         *
         * 320 -> 280
         * 480 -> 330
         * 640 -> 380
         * 720 -> 500
         *
         * HH = CUR_HEIGHT - 320
         * [40, 50, 260, 260, 220, 248, 300, 536]
         * */

        var maxStackedValue = thisChart.maxStackedValue;
        var data  = thisChart.data;
        var marginBottom = Math.floor(containerHeight  / 4);

        var margin = {top: 10, right: 10, bottom: marginBottom, left: 40};
        var margin2 = {top: Math.floor(1.1 * containerHeight  - marginBottom), right: 10, bottom: 20, left: 40};

        if(!thisChart.zoom) {
            margin2 = {top: 0, right: 0, bottom: 0, left: 0};
            margin.bottom = 20;
        }

        var width = containerWidth - margin.left - margin.right;
        var height = containerHeight - margin.top - margin.bottom;
        var height2 = containerHeight - margin2.top - margin2.bottom;
        var legendHeight = 50;

        var x, x2, x3, x3temp, y, y2;

        if(chartType == 'area' || chartType == 'line') {
            x = d3.scale.linear().range([0, width]);
            x2 = d3.scale.linear().range([0, width]);
            y = d3.scale.linear().range([height, 0]);
            y2 = d3.scale.linear().range([height2, 0]);

            x.domain(d3.extent(data.map(function (d) { return d["custom_"+nameForX].value; })));
            y.domain([0, maxStackedValue]);
            x2.domain(x.domain());
            y2.domain(y.domain());
            if (!thisChart.isNumericXAxis) {
                x3temp = d3.scale.linear().range([0, width]).domain(d3.extent(data.map(function (d) { return d["custom_"+nameForX].value; })));
                x3 = d3.scale.ordinal().range(data.map(function(d,i) { return x3temp(i) })).domain(data.map(function (d) { return d["custom_"+nameForX].alias}));
            } else {
                x3 = x
            }
        }
        else {
            x = d3.scale.ordinal()
                .domain(data.map(function (d) { return d["custom_"+nameForX].value}))
                .rangeRoundBands([0, width], .2);

            x2 = d3.scale.ordinal()
                .domain(data.map(function (d) { return d["custom_"+nameForX].value}))
                .rangeRoundBands([0, width], .2);

            x3 = d3.scale.ordinal()
                .domain(data.map(function (d) { return d["custom_"+nameForX].alias}))
                .rangeRoundBands([0, width], .2);

            y = d3.scale.linear().range([height, 0]);
            y2 = d3.scale.linear().range([height2, 0]);

            y.domain([0, maxStackedValue]);
            y2.domain(y.domain());
        }



        var xAxis = d3.svg.axis().scale(x3).orient("bottom"),
            xAxis2 = d3.svg.axis().scale(x3).orient("bottom"),
            yAxis = d3.svg.axis().scale(y).orient("left");

        var svgItem = document.getElementById(divId);

        var svg = d3.select(svgItem).append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom);

        var legend = d3.select('#'+divId).append('div').attr("class", "chartLegend");

        svg.append("defs").append("clipPath")
            .attr("id", "clip")
            .append("rect")
            .attr("width", width)
            .attr("height", height);

        var focus = svg.append("g")
            .attr("class", "focus")
            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        focus.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + height + ")")
            .call(xAxis);

        focus.append("g")
            .attr("class", "y axis")
            .call(yAxis);

        var context;
        var brush;

        if(thisChart.zoom) {
            context = svg.append("g")
                .attr("class", "context")
                .attr("transform", "translate(" + margin2.left + "," + margin2.top + ")");

            function brushed() {
                thisChart.brushFunction();
            }

            brush = d3.svg.brush().x(x2).on("brush", brushed);

            context.append("g")
                .attr("class", "x brush")
                .call(brush)
                .selectAll("rect")
                .attr("y", -6)
                .attr("height", height2 + 7);

            context.append("g")
                .attr("class", "x axis")
                .attr("transform", "translate(0," + height2 + ")")
                .call(xAxis2);

            thisChart.context = context;
            thisChart.brush = brush;
            thisChart.margin2 = margin2;
        }

        thisChart.margin = margin;
        thisChart.width = width;
        thisChart.height = height;
        thisChart.height2 = height2;

        thisChart.focus = focus;
        thisChart.svg = svg;
        thisChart.axisX = x;
        thisChart.axisX2 = x2;
        thisChart.axisX3 = x3;
        thisChart.axisY = y;
        thisChart.axisY2 = y2;
        thisChart.xAxis = xAxis;
        thisChart.xAxis2 = 123;
        thisChart.legend = legend;
    };

    this.brushFunction = function() {
        thisChart.brush.x(this.axisX2)

        var type = thisChart.type;

        var x = this.axisX;
        var y = this.axisY;
        var width = thisChart.width;

        if(type == 'line' || type == 'area') {
            var area = d3.svg.area().interpolate("basis")
                .x(function (d) { return x(d.coord); })
                .y0(function (d) { return (type == 'line') ? y(d.y) : y(0); })
                .y1(function (d) { return  y(d.y); });

            if(thisChart.zoom) {
                this.axisX.domain(this.brush.empty() ? this.axisX2.domain() : this.brush.extent());
            }

            this.focus.selectAll(".area").attr("d", function (d) { return area(d.values) });
            this.focus.select(".x.axis").call(thisChart.xAxis.scale());
        } else if(type == 'bar' || type == 'stacked_bar') {
            var xZoom = d3.scale.linear().range([0, width]).domain([0, width]);
            var originalRange = xZoom.range();

            if(thisChart.zoom) {
                xZoom.domain(this.brush.empty() ? originalRange : this.brush.extent());
            }

            x.rangeRoundBands([ xZoom(originalRange[0]), xZoom(originalRange[1])], 0.2);

            var x1;
            if(type == 'bar')
                 x1 = d3.scale.ordinal()
                     .domain(this.dataValues.map(function (v) {return v.name}))
                     .rangeRoundBands([0, x.rangeBand()], 0);

            this.focus.selectAll(".g").attr("transform", "translate(0, 0)");

            if( type == 'bar') {
                this.focus.selectAll("rect").attr("transform", function (d) { return "translate(" + x(d.coord) + ",0)";})
                    .attr("x", function (d) { return x1(d.name) })
                    .attr("width", x1.rangeBand())
            }
            else if(type == 'stacked_bar') {
                this.focus.selectAll("rect").attr("x", function (d) { return x(d.coord)}).attr("width", x.rangeBand());
            }

            this.focus.select(".x.axis").call(this.xAxis);
        }
    };

    this.hideOldObjects = function() {
        var margin = thisChart.margin;
        var duration = thisChart.duration;

        thisChart.focus
            .transition()
            .delay(duration)
            .duration(0)
            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        var hideObjects = function( objects ) {
            if(typeof objects !== 'undefined') {
                if (thisChart.withoutAnimation) {
                    objects.style("stroke-opacity", 0).style("fill-opacity", 0).remove();
                } else {
                    objects.transition().duration(duration).style("stroke-opacity", 0).style("fill-opacity", 0).remove();
            }
            }
        }

        hideObjects( thisChart.objects1 );
        hideObjects( thisChart.objects2 );
        hideObjects( thisChart.svg.selectAll('.g'));

    };
    this.showActualObjects = function( ) {
        var opacityValue = (thisChart.type == 'xpie' || thisChart.type == 'ypie') ? 0 : 1;
        var opacityValue2 = (thisChart.type == 'area') ? 0.5 : 1;
        var duration = thisChart.duration;
        d3.select('#' + divId).selectAll(".axis").style("stroke-opacity", opacityValue).style("fill-opacity", opacityValue);
        var showObjects = function( objects ) {
            if(typeof objects !== 'undefined') {
                if (thisChart.withoutAnimation)
                    objects.style("stroke-opacity", 1).style("fill-opacity", opacityValue2);
                else
                    objects.transition().delay(duration).duration(duration).style("stroke-opacity", 1).style("fill-opacity", opacityValue2);
            }
        };

        showObjects( thisChart.objects1 );
        showObjects( thisChart.objects2 );
        showObjects( thisChart.objects2 );
    };

    this.makeBar = function() {
        var barType = (thisChart.type == 'stacked_bar') ? 'stacked' : 'grouped';
        var focus = thisChart.focus;
        var context = thisChart.context;
        var data = thisChart.data;
        var dataValues = thisChart.dataValues;
        var x = thisChart.axisX;
        var y = thisChart.axisY;
        var y2 = thisChart.axisY2;
        var color = thisChart.color;
        var height = thisChart.height;
        var height2 = thisChart.height2;
        var rect;

        var state = focus.selectAll(".state")
            .data(data)
            .enter().append("g")
            .attr("class", "g")
            .attr("transform", function (d) {
                return "translate(" + x(d["custom_"+nameForX].value) + ",0)";
            });

        if (barType == "grouped") {
            var x1 = d3.scale.ordinal();
            x1.domain(dataValues.map(function (v) {
                return v.name
            })).rangeRoundBands([0, x.rangeBand()]);

            rect = state.selectAll("rect")
                .data(function (d) { return d.dataValues; })
                .enter().append("rect")
                .attr("width", x1.rangeBand())
                .attr("x", function (d) { return x1(d.name); })
                .attr("y", function (d) { return y(d.y - d.y0);  })
                .attr("height", function (d) { return height - y(d.y - d.y0); })
                .style("fill", function (d) { return color(d.name); })

        }
        else if (barType == "stacked") {
            rect = state.selectAll("rect")
                .data(function (d) { return d.dataValues; })
                .enter().append("rect")
                .attr("width", x.rangeBand())
                .attr("x", 0)
                .attr("y", function (d) { return y(d.y) ; })
                .attr("height", function (d) { return height - y(d.y - d.y0); /*y2(d.y0) - y(d.y);*/ })
                .style("fill", function (d) {
                    return color(d.name);
                });
        }

        var state2, rect2;
        if(thisChart.zoom) {
            state2 = context.selectAll(".state")
                .data(data)
                .enter().insert("g", ":first-child")
                .attr("class", "g")
                .attr("transform", function (d) {
                    return "translate(" + x(d["custom_"+nameForX].value) + ",0)";
                });

            if (barType == "grouped") {
                rect2 = state2.selectAll("rect")
                    .data(function (d) { return d.dataValues; })
                    .enter().append("rect")
                    .attr("width", x1.rangeBand())
                    .attr("x", function (d) { return x1(d.name); })
                    .attr("y", function (d) { return y2(d.y - d.y0); })
                    .attr("height", function (d) { return height2 - y2(d.y - d.y0); })
                    .style("fill", function (d) { return color(d.name); })
            }
            else if (barType == "stacked") {
                rect2 = state2.selectAll("rect")
                    .data(function (d) { return d.dataValues; })
                    .enter().append("rect")
                    .attr("width", x.rangeBand())
                    .attr("x", 0)
                    .attr("y", function (d) { return y2(d.y); })
                    .attr("height", function (d) { return y2(d.y0) - y2(d.y); })
                    .style("fill", function (d) { return color(d.name); })
            }
        }

        if(!thisChart.isVisible)
            focus.selectAll("rect").style("fill-opacity", 0.0);

        thisChart.objects1 = rect;

        if(thisChart.zoom) {
            if(!thisChart.isVisible)
                context.selectAll("rect").style("fill-opacity", 0.0);
            context.selectAll("rect.extent") .style("fill-opacity", 0.125);
            thisChart.objects2 = rect2;
            thisChart.context.style('display','inline');

        }
    };
    this.makeArea = function() {
        var areaType = thisChart.type;
        var focus = thisChart.focus;
        var dataValues = thisChart.dataValues;
        var x = thisChart.axisX;
        var y = thisChart.axisY;
        var y2 = thisChart.axisY2;
        var color = thisChart.color;
        var area, area2;
        var rect;

        if (areaType == "line") {
             area = d3.svg.area()
                .interpolate("basis")
                .x(function (d)  { return x(d.coord);})
                .y0(function (d) { return y(d.y); })
                .y1(function (d) { return y(d.y);});

             area2 = d3.svg.area()
                .interpolate("basis")
                .x(function (d)  { return x(d.coord); })
                .y0(function (d) { return y2(d.y); })
                .y1(function (d) { return y2(d.y); });
        }
        else if (areaType == "area") {
             area = d3.svg.area()
                .interpolate("basis")
                .x(function (d) { return x(d.coord);})
                .y0(function (d) { return y(0); })
                .y1(function (d) { return y(d.y);});

             area2 = d3.svg.area()
                .interpolate("basis")
                .x(function (d) { return x(d.coord); })
                .y0(function (d) { return y2(0); })
                .y1(function (d) { return y2(d.y); });
        }

        rect = focus.selectAll(".state")
            .data(dataValues)
            .enter().append("g")
            .attr("class", "g");

        rect.append("path")
            .attr("class", "area")
            .attr("d", function (d) { return area(d.values) });

        if (areaType == "area") {
            rect.selectAll("path")
                .style("fill", function (d) { return color(d.name); })
        }
        else if (areaType == "line") {
            rect.selectAll("path")
                .style("fill", function (d) { return color(d.name); })
                .style("fill-opacity", 0.0)
                .style("stroke", function (d) { return color(d.name); });
        }

        if (!thisChart.isVisible) {
            rect.selectAll("path").style("fill-opacity", 0.0).style("stroke-opacity", 0.0);
        }

        thisChart.objects1 = rect.selectAll("path");




        var rect2;

        if(thisChart.zoom) {
            rect2 = thisChart.context.selectAll(".state")
            .data(dataValues)
            .enter().insert("g", ":first-child")
            .attr("class", "g");

            rect2.append("path")
                .attr("class", "area")
                .attr("d", function (d) { return area2(d.values) });

            if (areaType == "area") {
                rect2.selectAll("path").style("fill", function (d) { return color(d.name); });
            }
            else if (areaType == "line") {
                rect2.selectAll("path")
                    .style("fill", function (d) { return color(d.name); })
                    .style("fill-opacity", 0.0)
                    .style("stroke", function (d) { return color(d.name); });
            }

            thisChart.context.style('display','inline');

            if (!thisChart.isVisible)
                rect2.selectAll("path").style("fill-opacity", 0.0).style("stroke-opacity", 0.0);

            thisChart.objects2 = rect2.selectAll("path");
        }


    };
    this.makePie = function() {
        var PieType = thisChart.type;
        var focus = thisChart.focus;
        var dataValues = thisChart.dataValues;
        var data = thisChart.data;
        var color = thisChart.color;
        var svg = thisChart.svg;
        var width = thisChart.width;
        var height = thisChart.height;
        var radius = Math.min(width, height) / 2;
        var radiusStep = radius / dataValues.length / 1.2

        var total = dataValues
            .map(function (v) { return v.name })
            .map(function (d) { return {
                name: d,
                value: d3.sum(data.map(function (v) { return v[d]}))
            }
        });

        var pie = d3.layout.pie().sort(null).value(function (d) { return d.value; });

        var printPie = function(data, count) {
             var arc = d3.svg.arc().outerRadius((radius - radiusStep * count) ).innerRadius((radius - radiusStep * (count+1) ) + 5 );
             var g = focus.selectAll(".arc .arc_"+count)
                .data(pie(data))
            .enter().append("g")
            .attr("class", "arc")
            .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

        g.append("path")
            .attr("d", arc)
            .style("fill", function (d) {
                return color(d.data.name);
            });

        if (thisChart.isVisible) {
            svg.selectAll(".axis")
                .style("stroke-opacity", 1e-6)
                .style("fill-opacity", 1e-6)
        }
        else {
            svg.selectAll(".axis").transition()
                .duration(500)
                .style("stroke-opacity", 1e-6)
                .style("fill-opacity", 1e-6);

            g.style("fill-opacity", 1e-6);

            g.transition()
                .delay(500)
                .duration(500)
                .style("fill-opacity", 1)
        }

        g.append("text")
            .attr("transform", function (d) { return "translate(" + arc.centroid(d) + ")"; })
            .attr("dy", ".35em")
            .style("text-anchor", "middle")
            .text(function (d) { return d.data.name; });
            if (count == 0) {
                thisChart.objects1 = g;
            } else {
                thisChart.objects1[0] = thisChart.objects1[0].concat(g[0]);
            }
        }

        if (PieType == 'ypie') {
            var pieData = dataValues
                .map(function (v) { return v.name })
                .map(function (d) { return {
                    name: d,
                    value: d3.sum(data.map(function (v) { return v[d]}))
                }
            });
            printPie(pieData, 0)
        }
        else if (PieType == 'xpie') {
            var pieData = dataValues
                .map(function (v) {
                    return v.values.map(function(d) {
                        return {
                            name: d.coordAlias,
                            value: d.y
                        }
                    })
                })

            var count = 0
            for (var i in pieData) {
                var data = pieData[i]
                printPie(data, count)
                count++
            }
        }

        thisChart.objects2 = undefined;

        if(thisChart.zoom) {
            thisChart.context.style('display','none');
        }
    };

    this.prepareDataForLineAndArea = function() {
        var width = thisChart.width;
        var x = d3.scale.linear().range([0, width]);
        var x2 = d3.scale.linear().range([0, width]);
        var y = thisChart.axisY;

        x.domain(d3.extent(thisChart.data.map(function (d) { return d["custom_"+nameForX].value; })));
        x2.domain(x.domain());
        y.domain([0, thisChart.maxStackedValue]);

        thisChart.axisX = x;
        thisChart.axisY = y;
        thisChart.axisX2 = x2;
    };
    this.prepareDataForBars = function() {
        var width = thisChart.width;
        var y = thisChart.axisY;

        y.domain([0, thisChart.maxStackedValue]);

        var x = d3.scale.ordinal()
            .domain(thisChart.data.map(function (d) { return d["custom_"+nameForX].value }))
            .rangeRoundBands([0, width], .2);

        var x2 = d3.scale.ordinal()
            .domain(thisChart.data.map(function (d) { return d["custom_"+nameForX].value }))
            .rangeRoundBands([0, width], .2);

        thisChart.axisX = x;
        thisChart.axisY = y;
        thisChart.axisX2 = x2;
    };

    this.redrawLineToArea = function() {
        var x = thisChart.axisX;
        var y = thisChart.axisY;
        var y2 = thisChart.axisY2;
        var color = thisChart.color ;

        var area = d3.svg.area()
            .interpolate("basis")
            .x(function (d) { return x(d.coord);})
            .y0(function (d) { return y(0); })
            .y1(function (d) { return y(d.y); });

        var area2 = d3.svg.area()
            .interpolate("basis")
            .x(function (d) { return x(d.coord); })
            .y0(function (d) { return y2(0); })
            .y1(function (d) { return y2(d.y); });

        var obj1 = thisChart.objects1;
        var obj2 = thisChart.objects2;

        obj1.transition()
            .delay(function (d, i, j) { return j * 10; })
            .duration(500)
            .attr("d", function (d) { return area(d.values) })
            .style("stroke-opacity", 1.0)
            .style("stroke", "black")
            .style("fill-opacity", 0.5)
            .style("fill", function (d) { return color(d.name) });

        obj2.transition()
            .delay(function (d, i, j) { return j * 10; })
            .duration(500)
            .attr("d", function (d) { return area2(d.values) })
            .style("stroke-opacity", 1.0)
            .style("stroke", "black")
            .style("fill-opacity", 0.5)
            .style("fill", function (d) { return color(d.name) });

    };
    this.redrawAreaToLine = function() {
        var x = thisChart.axisX;
        var y = thisChart.axisY;
        var y2 = thisChart.axisY2;
        var color = thisChart.color;

        var area = d3.svg.area()
            .interpolate("basis")
            .x(function (d) {return x(d.coord); })
            .y0(function (d) { return y(d.y); })
            .y1(function (d) { return y(d.y); });

        var area2 = d3.svg.area()
            .interpolate("basis")
            .x(function (d) { return x(d.coord); })
            .y0(function (d) { return y2(d.y); })
            .y1(function (d) { return y2(d.y); });

        var obj1 = thisChart.objects1;
        var obj2 = thisChart.objects2;

        obj1.transition()
            .delay(function (d, i, j) { return j * 10; })
            .duration(500)
            .attr("d", function (d) { return area(d.values) })
            .style("stroke-opacity", 1)
            .style("stroke", function (d) { return color(d.name) })
            .style("fill-opacity", 0);

        obj2.transition()
            .delay(function (d, i, j) { return j * 10; })
            .duration(500)
            .attr("d", function (d) { return area2(d.values) })
            .style("stroke-opacity", 1)
            .style("stroke", function (d) { return color(d.name) })
            .style("fill-opacity", 0)
    };
    this.redrawBarToStacked = function() {
        var x = thisChart.axisX;
        var y = thisChart.axisY;
        var y2 = thisChart.axisY2;
        var height = thisChart.height;
        var height2 = thisChart.height2;

        var groups = thisChart.focus.selectAll('.g');
        var obj1 = thisChart.objects1;
        var obj2 = thisChart.objects2;

        thisChart.focus.selectAll(".g")
            .attr("transform", function(d) {
            return "translate(" + x(d["custom_"+nameForX].value) + ",0)";
        });

        obj1.attr("transform", "");

        obj1.transition()
            .duration(500)
            .delay(function (d, i, j) { return j * 10; })
            .attr("y", function (d) { return y(d.y); })
            .attr("height", function (d) { return height - y(d.y - d.y0); })
            .transition()
            .attr("x", 0)
            .attr("width", x.rangeBand());

        obj2.attr("transform", "");

        obj2.transition()
            .duration(500)
            .delay(function (d, i, j) { return j * 10; })
            .attr("y", function (d) { return y2(d.y); })
            .attr("height", function (d) { return height2 - y2(d.y - d.y0); })
            .transition()
            .attr("x", 0)
            .attr("width", x.rangeBand());

    };
    this.redrawStackedToBar = function() {
        var x = thisChart.axisX;
        var y = thisChart.axisY;
        var y2 = thisChart.axisY2;
        var height = thisChart.height;
        var height2 = thisChart.height2;
        var n = thisChart.dataValues.length;

        thisChart.focus.selectAll(".g").attr("transform", function (d) {
            return "translate(" + x(d["custom_"+nameForX].value) + ",0)";
        });

        var obj1 = thisChart.objects1;
        var obj2 = thisChart.objects2;

        obj1.attr("transform", "");
        obj1.transition()
            .duration(500)
            .delay(function (d, i, j) { return j * 10; })
            .attr("x", function (d, i, j) { return x.rangeBand() / n * i; })
            .attr("width", x.rangeBand() / n)
            .transition()
            .attr("y", function (d) { return y(d.y - d.y0); })
            .attr("height", function (d) { return height - y(d.y - d.y0); });

        obj2.attr("transform", "");
        obj2.transition()
            .duration(500)
            .delay(function (d, i, j) { return j * 10; })
            .attr("x", function (d, i, j) { return x.rangeBand() / n * i; })
            .attr("width", x.rangeBand() / n)
            .transition()
            .attr("y", function (d) { return y2(d.y - d.y0); })
            .attr("height", function (d) { return height2 - y2(d.y - d.y0); });
    };

    this.addLegend = function() {
        var type = thisChart.type
        var legend = thisChart.legend
        legend.selectAll('.key').remove()
        var data = thisChart.data
        var dataValues = thisChart.dataValues;
        var color = thisChart.color;
        var opacity = (type == "area") ? .5 : 1

        if (type == 'xpie') {
            dataValues = data
        }

        var key = legend.selectAll('.key')
            .data(dataValues)
            .enter()
                .append("span")
                .attr("class", "key")
        key
            .append("span")
                .attr("class", "key_color")
                .style("background-color", function (d) { return color(d.name); })
                .style("background-opacity", opacity)
        key
            .append("span")
                .style("font-size", "12px")
                .text(function (d) { return d.name; });
    }

    this.redrawChart = function( actualType ) {
        var oldType = thisChart.type;
        thisChart.isVisible = false;

        if(oldType == actualType)
            return;

        thisChart.type = actualType;

        /* prepare data */
        if(actualType == 'line' ||  actualType == 'area') {
            thisChart.prepareDataForLineAndArea();
        } else if(actualType == 'bar' ||  actualType == 'stacked_bar') {
            thisChart.prepareDataForBars();
        }


        if(thisChart.zoom) {
            thisChart.brush.clear();
            thisChart.context.select(".extent").attr("width", 0);
        }

        /*
        * Possible variants:
        * 1. The old chart has the same type as new. Animate transition old objects in new;
        * 2. The old chart has the different type as new. Remove old objects (if existed) and draw new objects.
        * */

        if(oldType == 'line' && actualType == 'area' && !thisChart.withoutAnimation) {
            thisChart.redrawLineToArea();
        }
        else if(oldType == 'area' && actualType == 'line'&& !thisChart.withoutAnimation) {
            thisChart.redrawAreaToLine();
        }
        else if(oldType == 'bar' && actualType == 'stacked_bar' && !thisChart.withoutAnimation) {
            thisChart.redrawBarToStacked();
        }
        else if(oldType == 'stacked_bar' && actualType == 'bar' && !thisChart.withoutAnimation) {
            thisChart.redrawStackedToBar();
        }
        else {
            thisChart.hideOldObjects();

            if(actualType == 'line' || actualType == 'area') {
                thisChart.makeArea();
            } else if(actualType == 'bar' || actualType == 'stacked_bar') {
                thisChart.makeBar();
            } else if( actualType == 'xpie' || actualType == 'ypie') {
                thisChart.makePie();
            }

            thisChart.showActualObjects();
        }
        if (thisChart.withLegend) {
            thisChart.addLegend()
        }


        //thisChart.brushFunction();
    };

    this.init = function() {
        if(!thisChart.isReady) {
            thisChart.renderData();
            thisChart.initAxisAndContext();
            //d3.select(window).on('resize', thisChart.resize);
            thisChart.isReady = thisChart.isVisible = true;
        }

        thisChart.redrawChart( chartType );
    };
    thisChart.init();
};