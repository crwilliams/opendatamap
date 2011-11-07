/**
 * Namespace: Util.OS
 */
OpenLayers.Util.OS = {};

/**
 * Constant: MISSING_TILE_URL
 * {String} URL of image to display for missing tiles
 */
OpenLayers.Util.OS.MISSING_TILE_URL = "http://openstreetmap.org/openlayers/img/404.png";

/**
 * Property: originalOnImageLoadError
 * {Function} Original onImageLoadError function.
 */
OpenLayers.Util.OS.originalOnImageLoadError = OpenLayers.Util.onImageLoadError;

/**
 * Function: onImageLoadError
 */
OpenLayers.Util.onImageLoadError = function() {
    OpenLayers.Util.OS.originalOnImageLoadError;
};

/**
 * @requires OpenLayers/Layer/XYZ.js
 *
 * Class: OpenLayers.Layer.StreetView
 *
 * Inherits from:
 *  - <OpenLayers.Layer.XYZ>
 */
OpenLayers.Layer.StreetView = OpenLayers.Class(OpenLayers.Layer.XYZ, {
    /**
     * Constructor: OpenLayers.Layer.StreetView
     *
     * Parameters:
     * name - {String}
     * url - {String}
     * options - {Object} Hashtable of extra options to tag onto the layer
     */
    initialize: function(name, options) {
        var url = [
            "http://opendatamap.ecs.soton.ac.uk/dev/colin/appathon/tile/${z}/${x}/${y}.png"
        ];
        options = OpenLayers.Util.extend({
            numZoomLevels: 18,
            transitionEffect: "resize",
            sphericalMercator: true,
            attribution: "Contains Ordnance Survey data © Crown copyright and database right 2010"
        }, options);
        var newArguments = [name, url, options];
        OpenLayers.Layer.XYZ.prototype.initialize.apply(this, newArguments);
    },

    CLASS_NAME: "OpenLayers.Layer.StreetView"
});
