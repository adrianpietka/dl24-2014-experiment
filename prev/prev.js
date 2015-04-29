var Refresher = {
    time: 500,
    init: function() {
        setTimeout(function() {
            
                Refresher.refresh();
            
        }, Refresher.time);
    },
    refresh: function() {
        $.getJSON("../data/game-"+port+".json?t=" + new Date(), function(data) {
            var n = 0;
            var p = data["maze"].length > data["maze"][0].length ? data["maze"].length : data["maze"][0].length;
            
            Canvas.point = Canvas.width / p;
            Canvas.explorer = (Canvas.width / p) - 4;
            Canvas.dot = (Canvas.width / p) - 6;
            
            Canvas.clear();
            Canvas.drawBackground();
            
            console.log(data["timeToChange"]);
            
            $.each(data["maze"], function(y, xx) {
                y += 1;
                var points = xx.split('');
                
                $.each(points, function(x, type) {
                    x += 1;
                    
                    // niedostepne
                    if (type == "#") {
                        Canvas.drawPoint(x * Canvas.point, y * Canvas.point, "#000"); // czarny
                    // dostepne
                    } else if (type == ".") {
                        Canvas.drawPoint(x * Canvas.point, y * Canvas.point, "#87D327"); // zielony
                    // zbrojownia
                    } else if (type== "A") { 
                        Canvas.drawPoint(x * Canvas.point, y * Canvas.point, "#9E3000"); // brazowy
                    // wyjscie
                    } else if (type == "E") { 
                        Canvas.drawPoint(x * Canvas.point, y * Canvas.point, "#00BBFF"); // niebieski
                    // skarb
                    } else if (type == "T") { 
                        Canvas.drawPoint(x * Canvas.point, y * Canvas.point, "#E5FF00"); // zloty
                    // potwory
                    } else if (type == "@") { 
                        Canvas.drawPoint(x * Canvas.point, y * Canvas.point, "red"); // czerwony
                    }
                });
            });
            
            $.each(data["myExplorers"], function(i, data) {
                Canvas.drawExplorer(data.X * Canvas.point, data.Y * Canvas.point, "rebeccapurple"); // fioletowy
            });
            
            $.each(data["plannedPaths"], function(i, paths) {
                $.each(paths, function(i, path) {
                    Canvas.drawDot(path.X * Canvas.point, path.Y * Canvas.point, "pink"); // rozowy
                });
            });
            
            // explorerStates
        });
        
        Refresher.init();
    }
};

var Canvas = {
    width: 750,
    height: 750,
    point: 5,
    dot: 5,
    explorer: 5,
    drawBackground: function() {
        // x
        for (var x = 0; x <= Canvas.width; x += Canvas.point) {
            $("#canvas").drawLine({
                strokeStyle: '#aaa',
                strokeWidth: 1,
                rounded: false,
                x1: x,
                y1: 0,
                x2: x,
                y2: Canvas.height
            });
        }
        
        // y
        for (var y = 0; y <= Canvas.height; y += Canvas.point) {
            $("#canvas").drawLine({
                strokeStyle: '#aaa',
                strokeWidth: 1,
                rounded: false,
                x1: 0,
                y1: y,
                x2: Canvas.width,
                y2: y
            });
        }
    },
    drawExplorer: function(x, y, color) {
        $("#canvas").drawRect({
            fillStyle: color,
            x: x - (Canvas.point/2),
            y: y - (Canvas.point/2),
            width: Canvas.explorer,
            height: Canvas.explorer
        });
    },
    drawDot: function(x, y, color) {
        $("#canvas").drawRect({
            fillStyle: color,
            x: x - (Canvas.point/2),
            y: y - (Canvas.point/2),
            width: Canvas.dot,
            height: Canvas.dot
        });
    },
    drawPoint: function(x, y, color) {
        $("#canvas").drawRect({
            fillStyle: color,
            x: x - (Canvas.point/2),
            y: y - (Canvas.point/2),
            width: Canvas.point,
            height: Canvas.point
        });
    },
    clear: function() {
        $("#canvas").clearCanvas();
    }
};

var Error = {
    show: function(response) {
        $("#error").addClass("show");
        $("#error > .message > pre").html(response);
    },
    hide: function() {
        $("#error").removeClass("show");
    }
};

$(document).ready(function() {
    Refresher.init();
});