///<amd-dependancy path="Lightbox.js">
///<amd-dependancy path="sizzle.min.js">
var $ = Sizzle;
(function () {
    // initialize lightboxes
    _.each($('[data-lightbox]'), function (box) {
        if (box.hasAttribute('data-lightbox')) {
            var lightbox = new Lightbox(box.getAttribute('data-lightbox'), box);
        }
        else {
            console.debug('Deprecated use of lightbox! use data-lightbox="' + box.getAttribute('href') + '" instead of href="' + box.getAttribute('href') + '"');
            var lightbox = new Lightbox(box.getAttribute('href'), box);
        }
    });
    this.triggerEvent = function (element, eventName, args) {
        var event = new CustomEvent(eventName, {
            detail: args
        });
        element.dispatchEvent(event);
    };
})();
//# sourceMappingURL=atomar.js.map