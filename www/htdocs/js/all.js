/*jslint nomen: true*/
/*jslint browser: true */
/*jslint devel: true */

var xmlhttp = null;

google.maps.visualRefresh = true;

var map;
var version;
var postcodeMarkers = {};
var postcodeInfowindows = {};
var polygons = {};
var polygoninfowindows = {};
var polygonnames = {};
var polygonlls = {};
var hashfields = {};
var firstAtPos = {};
var tempInfowindow = null;

var DEFAULT_SEARCH_ICON = "http://www.picol.org/images/icons/files/png/32/search_32.png";
var CLEAR_SEARCH_ICON = "img/nt-left.png";

var selecteddate = null;

var zoomuri;
var clickuri;
var uri;

var contcount = 0;

var bb;

/**
 * Represents a collection of points of interest.
 * @constructor
 */
function PointsOfInterestCollection() {
    "use strict";
    this.uris = [];
    this.locations = [];
    this.visibleClusterMarkers = [];
    this.pointsOfInterest = {};
    this.pointsOfInterestByLocation = {};
    this.clusters = {};
    this.clusterInfoWindows = {};
    this.extraInfo = {};
    this.dynamicInfo = {};
}

var pointsOfInterestCollection = new PointsOfInterestCollection();

/**
 * Represents the search tool.
 * @constructor
 */
function SearchResults() {
    "use strict";
    this.exactMatch = false;
    this.resultCount = 0;
    this.selectIndex = -1;
    this.searchTerm = null;
    this.t = null;
}

var searchResults = new SearchResults();
/**
 * Represents a point of interest.
 * @constructor
 * @param uri - The URI of the point of interest.
 * @param position - The position of the point of interest.
 * @param label - The label of the point of interest.
 * @param icon - The URL of the icon of the point of interest.
 */
function PointOfInterest(uri, position, label, icon) {
    "use strict";
    this.uri = uri;
    this.position = position;
    this.label = label;
    this.icon = icon;
    this.marker = null;
    this.infoWindow = null;

    this.marker = new google.maps.Marker({
        position: this.position,
        title: this.label.replace('\\\'', '\''),
        map: map,
        icon: this.icon,
        visible: false
    });

    this.infoWindow = new google.maps.InfoWindow({ content: '<div id="content">' +
        '<h2 id="title"><img class="icon" style="width:20px;" src="' + this.icon + '" />' + this.label + '</h2>' +
        '<a class="odl" href="' + this.uri + '">Visit page</a><div id="bodyContent">Loading...</div></div>'});

    google.maps.event.addListener(this.marker, 'click', this.getPointOfInterestClickHandler(this));
}

var compare = function (a, b) {
    "use strict";
    return b - a;
};

var getHash = function (key) {
    "use strict";
    if (hashfields[key] !== undefined) {
        return hashfields[key];
    }
    return '';
};

var removePostcodeMarker = function (postcode) {
    "use strict";
    postcodeMarkers[postcode].setMap(null);
};

var zoomTo = function (uri, click, pan) {
    "use strict";
    var bounds, postcodeData, latlng, i, j, path, marker;
    click = (click !== undefined) ? click : true;
    pan = (pan !== undefined) ? pan : true;
    bounds = new google.maps.LatLngBounds();
    if (uri.substring(0, 9) === 'postcode:') {
        postcodeData = uri.substring(9).split(',');
        latlng = new google.maps.LatLng(postcodeData[1], postcodeData[2]);
        postcodeMarkers[postcodeData[0]] = new google.maps.Marker({
            position: latlng,
            map: map,
            title: postcodeData[0],
            icon: 'http://opendatamap.ecs.soton.ac.uk/resources/postcodeicon.php?pc=' + postcodeData[0]
        });
        postcodeInfowindows[postcodeData[0]] = new google.maps.InfoWindow({
            content: '<div id="content">' +
                '<h2 id="title">' + postcodeData[0] + '</h2>' +
                '<a class="odl" href="' + postcodeData[3] + '">Visit page</a><br />' +
                '<a class="odl" href="javascript:removePostcodeMarker(\'' + postcodeData[0] + '\')">Remove this marker</a>' +
                '</div>'
        });
        _gaq.push(['_trackEvent', 'JumpTo', 'Postcode', postcodeData[0]]);
        if (pan) { map.panTo(latlng); map.setZoom(15); }
    } else if (polygons[uri] !== undefined) {
        if (polygons[uri].length !== undefined) {
            _gaq.push(['_trackEvent', 'JumpTo', 'Polygon', uri]);
            for (i = 0; i < polygons[uri].length; i += 1) {
                path = polygons[uri][i].getPath().getArray();
                for (j = 0; j < path.length; j += 1) {
                    bounds.extend(path[j]);
                }
            }
            if (pan) { map.fitBounds(bounds); }
            if (click) { google.maps.event.trigger(polygons[uri][0], 'click', bounds.getCenter()); }
        } else {
            _gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
            if (pan) { map.panTo(polygons[uri].getPosition()); }
            if (click) { google.maps.event.trigger(polygons[uri], 'click'); }
        }
    } else if (pointsOfInterestCollection.contains(uri)) {
        _gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
        if (pan || click) {
            marker = pointsOfInterestCollection.getMarker(uri);
            if (pan) { map.panTo(marker.getPosition()); }
            if (click) { google.maps.event.trigger(marker, 'click'); }
        }
    } else if (uri === 'southampton-overview') {
        bounds.extend(new google.maps.LatLng(50.9667011, -1.4444580));
        bounds.extend(new google.maps.LatLng(50.9326431, -1.4438220));
        bounds.extend(new google.maps.LatLng(50.8887047, -1.3935115));
        bounds.extend(new google.maps.LatLng(50.9554826, -1.3560130));
        bounds.extend(new google.maps.LatLng(50.9667013, -1.4178855));
        if (pan) { map.fitBounds(bounds); }
    } else if (uri === 'southampton-centre') {
        bounds.extend(new google.maps.LatLng(50.9072471, -1.4186829));
        bounds.extend(new google.maps.LatLng(50.9111925, -1.4029262));
        bounds.extend(new google.maps.LatLng(50.9079644, -1.3979205));
        bounds.extend(new google.maps.LatLng(50.8930407, -1.4004233));
        if (pan) { map.fitBounds(bounds); }
    }
};

var renderClusterItem = function (uri, ll) {
    "use strict";
    var lltrim, onclick, marker, extraInfo;
    if (polygonlls[uri] === undefined) {
        lltrim = ll.replace(/\D/g, '_');
        onclick = "loadWindow('" + uri + "', $('#" + lltrim + "-content'), '" + ll + "')";
        marker = pointsOfInterestCollection.getMarker(uri);
        extraInfo = pointsOfInterestCollection.getExtraInfo(uri);
        return '<div class="clusteritem" onclick="' + onclick + '">' +
            '<img class="icon" src="' + marker.getIcon() + '" />' +
            marker.getTitle().replace('\\\'', '\'') + ' ' + extraInfo + '</div>';
    }
    return '';
};

var addControl = function (elementID, position) {
    "use strict";
    var element = document.getElementById(elementID);
    map.controls[position].push(element);
};

var geoloc = function () {
    "use strict";
    _gaq.push(['_trackEvent', 'Geolocation', 'Request']);
    navigator.geolocation.getCurrentPosition(
        function (position) {
            if (position.coords.accuracy < 5000) {
                map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
            } else {
                alert('Sorry, geo location wildly inaccurate (' + position.coords.accuracy + " meters)");
            }
            _gaq.push(['_trackEvent', 'Geolocation', 'Response', null, position.coords.accuracy]);
        },
        function () {
            alert('Sorry, geo location failed');
            _gaq.push(['_trackEvent', 'Geolocation', 'Failed']);
        }
    );
};

var resetSearchIcon = function () {
    "use strict";
    var val = $("#inputbox").val();
    if (val.length > 0) {
        $("#clear").attr("src", CLEAR_SEARCH_ICON);
    } else {
        $("#clear").attr("src", DEFAULT_SEARCH_ICON);
    }
};

var switchInfoWindows = function (from, to) {
    "use strict";
    var anchor = from.get('anchor');
    if (anchor !== undefined) {
        to.open(map, anchor);
    } else {
        to.setPosition(from.getPosition());
        to.open(map);
    }
    from.close();
};

// someone's clicked on something, you need to load the real data into it
var loadWindow = function (j, dest, reload) {
    "use strict";
    _gaq.push(['_trackEvent', 'InfoWindow', 'Single', j]);
    if (dest === undefined && polygonlls[j] !== undefined) {
        return;
    }
    $.get("info.php?v=" + version + "&date=" + selecteddate + "&uri=" + encodeURIComponent(j), function (data) {
        var ll, clusterTitle;
        ll = pointsOfInterestCollection.getMarker(j).getPosition().toString();
        clusterTitle = '';
        if (polygonnames[ll] !== undefined) {
            clusterTitle = '<h1>' + polygonnames[ll] + '</h1><hr />';
        }
        if (dest === undefined) {
            pointsOfInterestCollection.getInfoWindow(j).setContent(clusterTitle + data);
        } else {
            tempInfowindow = new google.maps.InfoWindow({
                content: clusterTitle + data +
                    '<a href="#" class="back" onclick="return goBack(\'' + reload + '\')\">Back to list</a>'
            });
            switchInfoWindows(pointsOfInterestCollection.getClusterInfoWindow(reload), tempInfowindow);
        }
    });
};

var goBack = function (reload) {
    "use strict";
    switchInfoWindows(tempInfowindow, pointsOfInterestCollection.getClusterInfoWindow(reload));
    return false;
};

var closeAll = function () {
    "use strict";
    var i;
    pointsOfInterestCollection.closeAllInfoWindows();
    for (i in polygoninfowindows) {
        if (polygoninfowindows.hasOwnProperty(i)) {
            polygoninfowindows[i].close();
        }
    }
    if (tempInfowindow !== null) {
        tempInfowindow.close();
    }
};

var cluster = function (reopen) {
    "use strict";
    closeAll();
    pointsOfInterestCollection.cluster();
    if (reopen !== undefined) {
        zoomTo(reopen, true, false);
    }
};

var fitBounds = function () {
    "use strict";
    var llnelat = [],
        llnelng = [],
        llswlat = [],
        llswlng = [],
        i;
    if (bb !== undefined) {
        for (i = 0; i < 100; i += 1) {
            if (!bb[i].isEmpty()) {
                llnelat.push(bb[i].getNorthEast().lat());
                llnelng.push(bb[i].getNorthEast().lng());
                llswlat.push(bb[i].getSouthWest().lat());
                llswlng.push(bb[i].getSouthWest().lng());
            }
        }
        llnelat.sort(compare);
        llnelng.sort(compare);
        llswlat.sort(compare);
        llswlng.sort(compare);
        llswlat.reverse();
        llswlng.reverse();
        map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(llswlat[10], llswlng[10]), new google.maps.LatLng(llnelat[10], llnelng[10])));
    }
};

var updateHash = function (key, value) {
    "use strict";
    var hashstring = '',
        i;
    if (key !== undefined && value !== undefined) {
        hashfields[key] = value;
    }
    for (i in hashfields) {
        if (hashfields.hasOwnProperty(i) && hashfields[i] !== '' && hashfields[i] !== undefined) {
            hashstring += ',' + i + '=' + hashfields[i];
        }
    }
    location.hash = '#' + hashstring.substring(1);
};

// Subject functions.

var refreshSubjectChoice = function () {
    "use strict";
    var str = getHash('subject');
    if (str === '') {
        $('.General').hide();
        $('.InformationStand').hide();
        $('.Subject').show();
        $('.Subject h2').show();
        $('.Subject li').hide();
        $('#selectedsubject').html("Choose a subject:");
    } else {
        $('.General').show();
        $('.InformationStand').show();
        $('.Subject').hide();
        $('.Subject h2').hide();
        $('.Subject.subj_' + str).show();
        $('.Subject.subj_' + str + ' li').show();
    }
};

var changeSubject = function () {
    "use strict";
    $('#inputbox').val('/' + getHash('day'));
    $('#selectedsubject').removeClass('clickable');
    $('#selectedsubject').attr('title', null);
    $('#selectedsubject').css('background-color', 'inherit');
    $('#selectedsubject').css('color', 'inherit');
    $('#selectedsubject').click(null);
    refreshSubjectChoice();
    searchResults.updateFunc();
    updateHash('subject', '');
};

var chooseSubject = function (name) {
    "use strict";
    $('#selectedsubject').html(name + '<br/><span style="font-size:0.8em">(click to change subject)</span>');
    $('#selectedsubject').addClass('clickable');
    $('#selectedsubject').attr('title', 'Click to change subject');
    $('#selectedsubject').css('background-color', '#007C92');
    $('#selectedsubject').css('color', 'white');
    $('#selectedsubject').click(changeSubject);
    refreshSubjectChoice();
};

// Hash functions.

var hashChange = function () {
    "use strict";
    var hashstring = location.hash.replace(/^#/, ''),
        hashstringparts = hashstring.split(','),
        hashfield,
        dates = {},
        fulldates = {},
        d,
        fulldate,
        s,
        hashvals = '',
        i;
    hashfields = {};
    for (i in hashstringparts) {
        if (hashstringparts.hasOwnProperty(i)) {
            hashfield = hashstringparts[i].split('=');
            hashfields[hashfield[0]] = hashfield[1];
        }
    }

    if (document.title.split(' | ')[0] !== 'University of Southampton Open Day Map') {
        return;
    }
    $('#day a').each(function (i, v) {
        var d = v.id.substring(5, 15);
        dates[v.innerHTML.toLowerCase()] = d;
        fulldates[v.innerHTML.toLowerCase()] = v.title.replace('Show ', '').replace('\'s events (', ' ').replace(')', '');
        $('._' + d).hide();
    });
    $('#day a').each(function (i, v) {
        var d = v.id.substring(5, 15);
        $('#link_' + d).removeClass('selected');
    });
    document.title = document.title.split(' | ')[0];

    d = getHash('day');
    if (d === '') {
        d = Object.keys(dates)[0];
        hashfields.day = d;
        updateHash();
    }
    if (dates[d] === undefined) {
        return;
    }
    selecteddate = dates[d];
    fulldate = fulldates[d];

    document.title += ' | ' + fulldate;
    $('._' + selecteddate).show();
    $('#link_' + selecteddate).addClass('selected');

    s = getHash('subject');
    if ($('#subj_' + s).get(0) !== undefined) {
        chooseSubject($('#subj_' + s).get(0).innerHTML);
    }

    if (hashfields.subject !== undefined) {
        hashvals += hashfields.subject;
    }
    hashvals += '/';
    if (hashfields.day !== undefined) {
        hashvals += hashfields.day;
    }
    $('#inputbox').val(hashvals);
    searchResults.updateFunc();
};

// Category functions.

var toggle = function (category) {
    "use strict";
    var cEl = $('#' + category.replace('/', '\\/')).get(0);
    if (cEl.checked) {
        cEl.checked = false;
        _gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 0]);
    } else {
        cEl.checked = true;
        _gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 1]);
    }
    searchResults.updateFunc(true);
};

var getSelectedCategories = function () {
    "use strict";
    var boxes = $('.togglebox'),
        selected = "",
        i;
    for (i = 0; i < boxes.length; i += 1) {
        if (boxes[i] !== null && boxes[i].checked) {
            selected += boxes[i].id + ",";
        }
    }
    return selected;
};

// Initilaization functions.

var initCredits = function () {
    "use strict";
    addControl('credits', google.maps.ControlPosition.RIGHT_BOTTOM);
    addControl('credits-small', google.maps.ControlPosition.RIGHT_BOTTOM);
};

var initToggle = function () {
    "use strict";
    addControl('toggleicons', google.maps.ControlPosition.RIGHT_TOP);
};

var initBookmarks = function () {
    "use strict";
    if ($('#bookmarks') === null) {
        return;
    }
    addControl('bookmarks', google.maps.ControlPosition.TOP_RIGHT);
    $('#bookmarks').show();
};

var initGeoloc = function () {
    "use strict";
    if (navigator.geolocation) {
        addControl('geobutton', google.maps.ControlPosition.TOP_RIGHT);
    } else {
        $('#geobutton').get(0).style.display = 'none';
    }
};

var initSearch = function () {
    "use strict";
    $('#search').index = 2;
    addControl('search', google.maps.ControlPosition.TOP_RIGHT);
    $('#search-small').index = 2;
    addControl('search-small', google.maps.ControlPosition.TOP_RIGHT);
};

var cont = function () {
    "use strict";
    contcount += 1;
    if (contcount !== 2) {
        return;
    }
    initGeoloc();
    initToggle();
    initBookmarks();
    initCredits();
    initSearch();

    $('#inputbox').keydown(function (evt) { searchResults.keypress(evt); });
    $('#inputbox').keyup(function () { searchResults.updateFunc(); });
    var hashstring = location.hash.replace(/^#/, '');
    location.hash = location.hash.split('/')[0];
    hashstring = hashstring.split('/');
    if (hashstring.length > 1) {
        hashstring = hashstring[1];
        $('#subj_' + hashstring).click();
    } else {
        hashstring = '';
    }
    if (uri) {
        searchResults.updateFunc(false, uri);
    } else if (clickuri) {
        searchResults.updateFunc(false, clickuri);
    } else {
        searchResults.updateFunc(false, undefined);
    }
    fitBounds();
    if (uri !== '') {
        zoomTo(uri, true, true);
    }
    if (zoomuri !== '') {
        zoomTo(zoomuri, false, true);
    }
    if (clickuri !== '') {
        zoomTo(clickuri, true, false);
    }
};

var initMarkers = function () {
    "use strict";
    $.get('alldata.php?v=' + version, function (data) {
        data.map(function (markpt) {
            if (markpt.length === 0) {
                return;
            }
            var pos = markpt[0],
                latitude = markpt[1],
                longitude = markpt[2],
                poslabel = markpt[3],
                icon = markpt[4],
                latlng = new google.maps.LatLng(latitude, longitude);
            pointsOfInterestCollection.add(new PointOfInterest(pos, latlng, poslabel, icon));
            if (bb !== undefined) {
                bb[Math.floor(Math.random() * 100)].extend(latlng);
            }
        });
        pointsOfInterestCollection.prepareClusters();
        cont();
    }, 'json');
    $.get('polygons.php?v=' + version, function (data) {
        var buildingIcon;

        polygons = {};
        polygoninfowindows = {};
        polygonnames = {};
        polygonlls = {};
        buildingIcon = new google.maps.MarkerImage('img/building.png',
            new google.maps.Size(20, 20),
            new google.maps.Point(0, 0),
            new google.maps.Point(10, 10));
        data.map(function (markpt) {
            if (markpt.length === 0) {
                return;
            }
            var pos = markpt[0],
                poslabel = markpt[1],
                zindex = markpt[2],
                points = markpt[3],
                color = markpt[4],
                ll = new google.maps.LatLng(markpt[5][1], markpt[5][0]).toString(),
                paths = [],
                polygonType = 'Building',
                fillColor = '#694B28',
                strokeColor = '#694B28',
                listener,
                position,
                bounds,
                path,
                i;
            polygonnames[ll] = poslabel;
            polygonlls[pos] = ll;
            for (i = 0; i < points.length - 1; i += 1) {
                paths.push(new google.maps.LatLng(points[i][1], points[i][0]));
            }
            if (paths.length === 0) {
                if (polygons[pos] === undefined) {
                    polygons[pos] = [];
                }
                polygons[pos] = new google.maps.Marker({
                    position: new google.maps.LatLng(points[i][1], points[i][0]),
                    icon: buildingIcon,
                    map: map,
                    visible: true
                });
            } else {
                if (zindex === -10) {
                    fillColor = '#2B7413';
                    strokeColor = '#2B7413';
                    polygonType = 'Site';
                }

                if (color !== '') {
                    fillColor = color;
                    strokeColor = color;
                }

                if (polygons[pos] === undefined) {
                    polygons[pos] = [];
                }
                polygons[pos].push(new google.maps.Polygon({
                    paths: paths,
                    title: poslabel,
                    map: map,
                    zIndex: zindex,
                    fillColor: fillColor,
                    fillOpacity: 0.2,
                    strokeColor: strokeColor,
                    strokeOpacity: 1.0,
                    strokeWeight: 2.0,
                    visible: true
                }));
            }
            polygoninfowindows[pos] = new google.maps.InfoWindow({ content:
                '<div id="content"><h2 id="title">' + poslabel + '</h2></div>'});

            if (paths.length === 0) {
                listener = polygons[pos];
                position = listener.getPosition();
            } else {
                listener = polygons[pos][polygons[pos].length - 1];
                bounds = new google.maps.LatLngBounds();
                path = listener.getPath().getArray();
                for (i = 0; i < path.length; i += 1) {
                    bounds.extend(path[i]);
                }
                position = bounds.getCenter();
            }
            google.maps.event.addListener(listener, 'click', function (event) {
                var infowindow = polygoninfowindows[pos],
                    requireload = false;
                closeAll();
                _gaq.push(['_trackEvent', 'InfoWindow', polygonType, pos]);
                if (polygonlls[pos] !== undefined && pointsOfInterestCollection.getClusterInfoWindow(polygonlls[pos]) !== undefined) {
                    infowindow = pointsOfInterestCollection.getClusterInfoWindow(polygonlls[pos]);
                } else if (firstAtPos[polygonlls[pos]] && pointsOfInterestCollection.contains(firstAtPos[polygonlls[pos]])) {
                    infowindow = pointsOfInterestCollection.getInfoWindow(firstAtPos[polygonlls[pos]]);
                    requireload = firstAtPos[polygonlls[pos]];
                }
                if (event !== undefined) {
                    if (event.latLng !== undefined) {
                        infowindow.setPosition(event.latLng);
                    } else {
                        infowindow.setPosition(position);
                    }
                    infowindow.open(map);
                } else {
                    infowindow.open(map, listener);
                }
                if (requireload) {
                    loadWindow(requireload);
                }
            });
        });
        cont();
    }, 'json');
    pointsOfInterestCollection.prepareExtraInfo();
};

/**
* @param latitude - The initial latitude.
* @param longitude - The initial longitude.
* @param zoom - The initial zoom level.
* @param puri - The URI of the point of interest to zoom to and click on.
* @param pzoomuri - The URI of the point of interest to zoom to.
* @param pclickuri - The URI of the point of interest to click on.
* @param pversion - The version of the application.
* @param defaultMap - The initial may style.
 */
var initialize = function (latitude, longitude, zoom, puri, pzoomuri, pclickuri, pversion, defaultMap) {
    "use strict";
    var i;
    zoomuri = pzoomuri;
    clickuri = pclickuri;
    uri = puri;
    version = pversion;
    if (zoom < 0) {
        bb = [];
        for (i = 0; i < 100; i += 1) {
            bb[i] = new google.maps.LatLngBounds();
        }
    }
    map = new google.maps.Map($('#map_canvas').get(0), {
        zoom: Math.abs(zoom),
        center: new google.maps.LatLng(latitude, longitude),
        mapTypeControlOptions: {
            mapTypeIds: ['Map2', google.maps.MapTypeId.SATELLITE],
            style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
            position: google.maps.ControlPosition.TOP_LEFT
        },
        mapTypeId: defaultMap
    });

    map.mapTypes.set('Map2', new google.maps.StyledMapType([
        {
            featureType: "poi",
            elementType: "all",
            stylers: [
                { visibility: "off" }
            ]
        }, {
            featureType: "landscape.man_made",
            elementType: "all",
            stylers: [
                { visibility: "off" }
            ]
        }, {
            featureType: "transit.station",
            elementType: "all",
            stylers: [
                { visibility: "off" }
            ]
        }
    ], {name: 'Map'}));

    initMarkers();

    $(window).bind('hashchange', hashChange);

    hashChange();
};

// Classes

/**
 * Set the value in the search box.
 * @param {string} str - The string to enter in the search box.
 * @param {bool} exact - Whether or not the search requires an exact match.
 */
SearchResults.prototype.setInputBox = function (str, exact) {
    "use strict";
    if (exact === true) {
        this.exactMatch = true;
    } else {
        this.exactMatch = false;
    }
    $('#inputbox').get(0).value = str;
};

/**
 * Update search results.
 * @param {bool} force - Whether or not to force an update.
 * @param reopen - The URI of the point of interest which requires its InfoWindow to be reopened (if any).
 */
SearchResults.prototype.updateFunc = function (force, reopen) {
    "use strict";
    if (force !== true) {
        force = false;
    }
    var enabledCategories = getSelectedCategories(),
        inputbox = $("#inputbox").get(0),
        newSearchTerm = inputbox.value;

    resetSearchIcon();

    if (this.exactMatch) {
        newSearchTerm = '^' + newSearchTerm + '$';
    }
    if (!force && newSearchTerm === this.searchTerm) {
        return;
    }
    this.searchTerm = newSearchTerm;

    if (xmlhttp !== null) {
        xmlhttp.abort();
    }
    xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "matches.php?v=" + version + "&q=" + this.searchTerm + '&ec=' + enabledCategories, true);
    _gaq.push(['_trackEvent', 'Search', 'Request', this.searchTerm]);
    xmlhttp.send();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            var response_data = JSON.parse(xmlhttp.responseText),
                matches = [],
                labelmatches = [];
            if (response_data !== undefined) {
                matches = response_data[0];
                labelmatches = response_data[1];
            }
            searchResults.processResponse(matches, labelmatches, reopen);
        }
    };
};

/**
 * Process the search response.
 * @param matches - The set of matches.
 * @param labelmatches - The set of matched labels.
 * @param reopen - The URI of the point of interest which requires its InfoWindow to be reopened (if any).
 */
SearchResults.prototype.processResponse = function (matches, labelmatches, reopen) {
    "use strict";
    var list = $("#list").get(0),
        matchesd = {},
        allURIs,
        i,
        uri,
        re,
        dispStr,
        onclick,
        escapeLabelmatch,
        element;
    matches.map(function (x) {
        if (x !== undefined) {
            matchesd[x] = true;
        }
    });

    allURIs = pointsOfInterestCollection.getAllURIs();
    for (i = 0; i < allURIs.length; i += 1) {
        uri = allURIs[i];
        pointsOfInterestCollection.getMarker(uri).setVisible(matchesd[uri] !== undefined);
    }

    this.selectIndex = -1;
    list.innerHTML = "";

    re = new RegExp('(' + $.trim(this.searchTerm) + ')', "gi");
    this.resultCount = 0;
    for (i = 0; i < labelmatches.length - 1; i += 1) {
        if (labelmatches[i][0] !== undefined) {
            dispStr = labelmatches[i][0];
            if (this.searchTerm !== "") {
                dispStr = String(dispStr).replace(re,
                    "<span class='search-highlight'>$1</span>");
            }
            onclick = '';
            escapeLabelmatch = labelmatches[i][0].replace('(', '\\\\(').replace(')', '\\\\)');
            if (labelmatches[i][2] !== undefined) {
                if (labelmatches[i][2] !== null) {
                    onclick = "zoomTo('" + labelmatches[i][2] + "');" +
                        "searchResults.setInputBox('');" +
                        "searchResults.updateFunc(false, '" + labelmatches[i][2] + "');";
                } else {
                    onclick = "searchResults.setInputBox('" + escapeLabelmatch + "', true);" +
                        "searchResults.updateFunc();";
                }
                element = '<li id="li' + this.resultCount + '" onclick="' + onclick + '">';
                if (labelmatches[i][3] !== undefined) {
                    element += '<img class="icon" src="' + labelmatches[i][3] + '" />';
                } else {
                    element += '<span style="font-size:0.5em">' + labelmatches[i][1] + ': </span>';
                }
                element += dispStr;
                element += '</li>';
                list.innerHTML += element;
            } else {
                onclick = "searchResults.setInputBox('" + escapeLabelmatch + "', true);" +
                    "searchResults.updateFunc();";
                list.innerHTML += '<li id="li' + this.resultCount + '" onclick="' + onclick + '">' + dispStr + '</li>';
            }
            this.resultCount += 1;
        }
    }
    if (this.resultCount === 0) {
        list.innerHTML += '<li><i>No results found</i></li>';
    }
    cluster(reopen);

    if ($("#spinner").is(":visible")) {
        $("#spinner").fadeOut();
    }
};

/**
 * Handle key presses within the search results.
 * @param e - The event.
 */
SearchResults.prototype.keypress = function (e) {
    "use strict";
    if (e.keyCode === 40) {
        return this.moveDown();
    }
    if (e.keyCode === 38) {
        return this.moveUp();
    }
    if (e.keyCode === 13) {
        return this.select();
    }
    if (e.keyCode === 27) {
        return this.blursearch();
    }
};

/** Handle moving upwards within the search results. */
SearchResults.prototype.moveUp = function () {
    "use strict";
    this.removeHighlight();
    if (this.selectIndex >= 0) {
        this.selectIndex -= 1;
    }
    this.updateHighlight();
    return false;
};

/** Handle moving downwards within the search results. */
SearchResults.prototype.moveDown = function () {
    "use strict";
    this.removeHighlight();
    if (this.selectIndex < this.resultCount - 1) {
        this.selectIndex += 1;
    }
    this.updateHighlight();
    return false;
};

/** Handle focus on the search results. */
SearchResults.prototype.enter = function () {
    "use strict";
    this.exactMatch = false;
    this.updateFunc();
    this.showList();
    $('#search').css('z-index', 10);
};

/** Handle selection within the search results. */
SearchResults.prototype.select = function () {
    "use strict";
    if (this.selectIndex >= 0) {
        $('#li' + this.selectIndex).get(0).onclick();
    }
    this.blursearch();
};

/** Handle unfocus on the search results. */
SearchResults.prototype.blursearch = function () {
    "use strict";
    this.removeHighlight();
    $('#inputbox').blur();
};

/** Remove the highlight from the search results. */
SearchResults.prototype.removeHighlight = function () {
    "use strict";
    if (this.selectIndex >= 0) {
        $('#li' + this.selectIndex).removeClass('selected');
    }
};

/** Apply the highlight to the search results. */
SearchResults.prototype.updateHighlight = function () {
    "use strict";
    if (this.selectIndex >= 0) {
        $('#li' + this.selectIndex).addClass('selected');
    }
};

/** Show the search results list. */
SearchResults.prototype.showList = function () {
    "use strict";
    this.selectIndex = -1;
    clearTimeout(this.t);
    $('#list').get(0).style.display = "block";
    $('#toggleicons').get(0).style.zIndex = 5;
};

/** Hide the search results list. */
SearchResults.prototype.hideList = function () {
    "use strict";
    $('#list').get(0).style.display = "none";
};

/** Hide the search results list after a 1 second delay. */
SearchResults.prototype.delayHideList = function () {
    "use strict";
    this.t = setTimeout(searchResults.hideList, 1000);
};

/**
 * Render the content for a cluster InfoWindow.
 * @param pointsOfInterest - The set of points of interest.
 * @param location - The location of the cluster.
 */
var renderContent = function (pointsOfInterest, location) {
    "use strict";
    var polygonname = polygonnames[location],
        clusterTitle = '',
        id = location.replace(/\D/g, '_'),
        pre,
        post,
        params = [],
        i;
    if (polygonname !== undefined) {
        clusterTitle = '<h1>' + polygonname + '</h1>';
        if (pointsOfInterest.length > 0) {
            clusterTitle += '<hr />';
        }
    }
    pre = clusterTitle + '<div id="' + id + '-listcontent">';
    post = '</div><div id="' + id + '-content"></div>';
    for (i = 0; i < pointsOfInterest.length; i += 1) {
        params.push(renderClusterItem(pointsOfInterest[i].getURI(), location));
    }
    if (pointsOfInterest.length > 0) {
        post = '<div class="listcontent-footer">click icon for more information</div>' + post;
    }
    return pre + params.join('') + post;
};

/**
 * Shorten an icon URL
 * @param iconURL - The icon URL.
 */
var shorten = function (iconURL) {
    return iconURL.replace(
        /http:\/\/data.southampton.ac.uk\/map-icons\/(.*)\.png/, 'soton:$1').replace(
        /http:\/\/opendatamap.ecs.soton.ac.uk\/resources\/workstationicon.php\?pos=http:\/\/id.southampton.ac.uk\/point-of-service\/(.*)/, 'ws:$1'
        );
};

/**
 * Get the icon URL for a set of points of interest
 * @param pointsOfInterest - The set of points of interest.
 */
var getIconURL = function (pointsOfInterest) {
    "use strict";
    var url = 'resources/clustericon/',
        params = [],
        i;
    for (i = 0; i < pointsOfInterest.length; i += 1) {
        params.push(shorten(pointsOfInterest[i].getMarker().getIcon()));
    }
    return url + params.join('|');
};

/**
 * Add a point of interest to the collection.
 * @param {PointOfInterest} pointOfInterest - The PointOfInterest to add.
 */
PointsOfInterestCollection.prototype.add = function (pointOfInterest) {
    "use strict";
    var uri = pointOfInterest.getURI(),
        ll;
    this.pointsOfInterest[uri] = pointOfInterest;
    this.uris.push(uri);
    ll = pointOfInterest.getPosition().toString();
    if (this.pointsOfInterestByLocation[ll] === undefined) {
        this.pointsOfInterestByLocation[ll] = [];
        this.locations.push(ll);
    }
    this.pointsOfInterestByLocation[ll].push(pointOfInterest);
};

/** Prepare the set of cluster locations. */
PointsOfInterestCollection.prototype.prepareClusters = function () {
    "use strict";
    var location,
        i;
    this.clusters = {};
    for (i = 0; i < this.locations.length; i += 1) {
        location = this.locations[i];
        if (this.pointsOfInterestByLocation[location].length > 1) {
            this.clusters[location] = this.pointsOfInterestByLocation[location];
        }
    }
};

/** Perform the clustering of the markers. */
PointsOfInterestCollection.prototype.cluster = function () {
    "use strict";
    var i,
        location,
        pointsOfInterest,
        visiblePointsOfInterest,
        pointOfInterest,
        clusterMarker,
        clusterInfoWindow;
    for (i = 0; i < this.visibleClusterMarkers.length; i += 1) {
        this.visibleClusterMarkers[i].setMap(null);
    }
    this.visibleClusterMarkers = [];
    for (location in this.clusters) {
        if (this.clusters.hasOwnProperty(location)) {
            pointsOfInterest = this.clusters[location];
            visiblePointsOfInterest = [];
            for (i = 0; i < pointsOfInterest.length; i += 1) {
                pointOfInterest = pointsOfInterest[i];
                if (pointOfInterest.getMarker().getVisible() === true) {
                    visiblePointsOfInterest.push(pointOfInterest);
                }
            }
            if (visiblePointsOfInterest.length > 1) {
                for (i = 0; i < pointsOfInterest.length; i += 1) {
                    pointsOfInterest[i].getMarker().setVisible(false);
                }
                clusterMarker = new google.maps.Marker({
                    position: visiblePointsOfInterest[0].getMarker().getPosition(),
                    title: visiblePointsOfInterest.length + ' items',
                    map: map,
                    icon: getIconURL(visiblePointsOfInterest),
                    visible: true
                });
                this.visibleClusterMarkers.push(clusterMarker);
                clusterInfoWindow = new google.maps.InfoWindow({
                    content: renderContent(visiblePointsOfInterest, location)
                });
                google.maps.event.addListener(
                    clusterMarker,
                    'click',
                    this.getClusterMarkerClickHandler(location, clusterInfoWindow, clusterMarker)
                );
                this.clusterInfoWindows[location] = clusterInfoWindow;
            } else if (visiblePointsOfInterest.length === 1) {
                this.clusterInfoWindows[location] = visiblePointsOfInterest[0].getInfoWindow();
            } else {
                this.clusterInfoWindows[location] = new google.maps.InfoWindow({
                    content: renderContent(visiblePointsOfInterest, location)
                });
            }
        }
    }
};

/** Get a cluster marker click handler. */
PointsOfInterestCollection.prototype.getClusterMarkerClickHandler = function (location, clusterInfoWindow, clusterMarker) {
    "use strict";
    return function () {
        closeAll();
        _gaq.push(['_trackEvent', 'InfoWindow', 'Cluster', location]);
        clusterInfoWindow.open(map, clusterMarker);
        pointsOfInterestCollection.updateExtraInfo();
    };
};

/**
 * Get the Marker associated with the point of interest with the given URI.
 * @param uri - The URI of the point of interest.
 */
PointsOfInterestCollection.prototype.getMarker = function (uri) {
    "use strict";
    return this.pointsOfInterest[uri].getMarker();
};

/**
 * Get the InfoWindow associated with the point of interest with the given URI.
 * @param uri - The URI of the point of interest.
 */
PointsOfInterestCollection.prototype.getInfoWindow = function (uri) {
    "use strict";
    return this.pointsOfInterest[uri].getInfoWindow();
};

/**
 * Get the extra info associated with the point of interest with the given URI.
 * @param uri - The URI of the point of interest.
 */
PointsOfInterestCollection.prototype.getExtraInfo = function (uri) {
    "use strict";
    var extraInfo = '';
    if (this.extraInfo[uri] !== undefined) {
        if (this.dynamicInfo[uri] !== undefined) {
            extraInfo = '<span class="extra" id="extra_' + this.dynamicInfo[uri] + '">' + this.extraInfo[uri] + '</span>';
        } else {
            extraInfo = '<span class="extra">' + this.extraInfo[uri] + '</span>';
        }
    }
    return extraInfo;
};

/**
 * Check whether the collection contains a point of interest with the given URI.
 * @param uri - The URI of the point of interest.
 */
PointsOfInterestCollection.prototype.contains = function (uri) {
    "use strict";
    return this.pointsOfInterest[uri] !== undefined;
};

/** Get all of the URIs of the points of interest in the collection. */
PointsOfInterestCollection.prototype.getAllURIs = function () {
    "use strict";
    return this.uris;
};

/** Close all of the InfoWindows associated with the points of interest in the collection. */
PointsOfInterestCollection.prototype.closeAllInfoWindows = function () {
    "use strict";
    var uri,
        i;
    for (uri in this.pointsOfInterest) {
        if (this.pointsOfInterest.hasOwnProperty(uri)) {
            this.pointsOfInterest[uri].getInfoWindow().close();
        }
    }

    for (i in this.clusterInfoWindows) {
        if (this.clusterInfoWindows.hasOwnProperty(i)) {
            this.clusterInfoWindows[i].close();
        }
    }
};

/**
 * Get the ClusterInfoWindow associated with a position.
 * @param {string} position - The position of the Cluster.
 */
PointsOfInterestCollection.prototype.getClusterInfoWindow = function (position) {
    "use strict";
    return this.clusterInfoWindows[position];
};

/**
 * Process the extra info.
 */
PointsOfInterestCollection.prototype.processExtraInfo = function (response_data) {
    "use strict";
    var i,
        item;
    if (response_data !== undefined) {
        for (i = 0; i < response_data.length; i += 1) {
            item = response_data[i];
            this.extraInfo[item[0]] = item[1];
            if (item[2] !== '') {
                this.dynamicInfo[item[0]] = item[2];
            }
        }
    }
    setInterval(this.updateExtraInfo, 60000);
};

/**
 * Process the extra info update.
 */
PointsOfInterestCollection.prototype.processExtraInfoUpdate = function (response_data) {
    "use strict";
    var i,
        uri,
        item,
        update = {},
        hash,
        newvalue,
        el;
    if (response_data !== undefined) {
        for (i = 0; i < response_data.length; i += 1) {
            item = response_data[i];
            update[item[0]] = item[1];
        }
        for (uri in this.dynamicInfo) {
            if (this.dynamicInfo.hasOwnProperty(uri)) {
                hash = this.dynamicInfo[uri];
                newvalue = update[hash];
                this.extraInfo[uri] = newvalue;
                el = $('#extra_' + hash);
                if (el !== undefined) {
                    el.text(newvalue);
                }
            }
        }
    }
};

/**
 * Prepare the extra info.
 */
PointsOfInterestCollection.prototype.prepareExtraInfo = function () {
    "use strict";
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "extrainfo.php?v=" + version, true);
    xmlhttp.send();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            var response_data = JSON.parse(xmlhttp.responseText);
            pointsOfInterestCollection.processExtraInfo(response_data);
        }
    };
};

/**
 * Update the extra info.
 */
PointsOfInterestCollection.prototype.updateExtraInfo = function () {
    "use strict";
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "extrainfo.php?v=" + version + '&update=true', true);
    xmlhttp.send();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            var response_data = JSON.parse(xmlhttp.responseText);
            pointsOfInterestCollection.processExtraInfoUpdate(response_data);
        }
    };
};

/** Get a point of interest click handler. */
PointOfInterest.prototype.getPointOfInterestClickHandler = function (pointOfInterest) {
    "use strict";
    return function () {
        closeAll();
        pointOfInterest.infoWindow.open(map, pointOfInterest.marker);
        loadWindow(pointOfInterest.getURI());
    };
};

/** Get the Marker associated with the point of interest. */
PointOfInterest.prototype.getMarker = function () {
    "use strict";
    return this.marker;
};

/** Get the InfoWindow associated with the point of interest. */
PointOfInterest.prototype.getInfoWindow = function () {
    "use strict";
    return this.infoWindow;
};

/** Get extra info associated with the point of interest. */
PointOfInterest.prototype.getExtraInfo = function () {
    "use strict";
    return this.extraInfo;
};

/** Get the URI of the point of interest. */
PointOfInterest.prototype.getURI = function () {
    "use strict";
    return this.uri;
};

/** Get the position of the point of interest. */
PointOfInterest.prototype.getPosition = function () {
    "use strict";
    return this.position;
};
