function JSLoader(isParent) {
  if(typeof isParent === 'undefined') isParent = true;
  var self = this;
  self.isParent = isParent; // used to style the global loader as static
  self.isStopped = true;
  self.loader = null;
  self.container = null;
  self.sheet = $('<div class="sheet"/>');
  self.spinner = $('<div class="spinner"/>').append($('<div class="circle1"/><div class="circle2"/>'));
  self.glass = $('<div class="glass"/>');
  self.target = null;
  self.is_blocking = true;
  self.errors = false;

  function __init() {
    if(supportsCSS3()) {
      // build loader
      self.container = $('<div>').addClass('loader-animation');
      self.container.append(self.sheet, self.spinner, self.glass);
      // set attachment mode
      if(self.isParent) {
        self.container.addClass('fixed');
      } else {
        self.container.removeClass('fixed');
      }
      // set blocking mode
      self.blocking(true);
      return null;
    }
    // prepare sonic loader animation
    // http://james.padolsey.com/javascript/sonic-looping-loaders/
    try {
      self.loader = new Sonic({
        width: 50,
        height: 50,

        stepsPerFrame: 1,
        trailLength: 1,
        pointDistance: 0.05,

        strokeColor: '#05E2FF',

        fps: 20,

        setup: function() {
          this._.lineWidth = 2;
        },
        step: function(point, index) {

          var cx = this.padding + 25,
            cy = this.padding + 25,
            _ = this._,
            angle = (Math.PI/180) * (point.progress * 360);

          _.beginPath();
          _.moveTo(point.x, point.y);
          _.lineTo(
            (Math.cos(angle) * 10) + cx,
            (Math.sin(angle) * 10) + cy
          );
          _.closePath();
          _.stroke();
        },
        path: [
          ['arc', 25, 25, 20, 0, 360]
        ]
      });
    } catch (err) {
      self.errors = true;
      return null;
    }
    self.blocking(true);
  }

  /**
   * Checks if the browser supports css3 properties
   */
  function supportsCSS3() {
    return ('WebkitTransform' in document.body.style
      || 'MozTransform' in document.body.style
      || 'OTransform' in document.body.style
      || 'transform' in document.body.style
      || 'WebkitAnimation' in document.body.style
      || 'MozAnimation' in document.body.style
      || 'OAnimation' in document.body.style
      || 'animation' in document.body.style);
  }

// display the loading animation
  self.start = function() {
    self.isStopped = false;
    if(supportsCSS3()) {
      self.startCSS3();
    } else {
      if (self.errors) return null;
      if (self.target != null) {
        self.target.append(self.container);
      } else {
        $(document.body).append(self.container);
      }
      if (!self.loader.stopped) {
        self.loader.stop();
      }
      self.loader.play();
      self.container.fadeIn(200);
    }
  }

  self.startCSS3 = function() {
    if (self.target != null) {
      self.target.append(self.container);
    } else {
      $(document.body).append(self.container);
    }
    self.container.fadeOut(0).fadeIn(200);
  }

  self.setTarget = function(selector) {
    if (self.errors) return null;
    self.target = selector;
    self.resize();
    // adjust size to fit when the window is resized
    $(window).resize(function() {
      self.resize();
    });
  }

  self.resize = function() {
    if(supportsCSS3()) {
      self.resizeCSS3();
    } else {
      if (self.errors) return null;
      if (self.is_blocking) {
        try {
          self.container.css('width', self.target.width()
            + +self.target.css('padding-left').slice(0, -2)
            + +self.target.css('padding-right').slice(0, -2)
            + +self.target.css('margin-left').slice(0, -2)
            + +self.target.css('margin-right').slice(0, -2));
          self.container.css('height', self.target.height()
            + +self.target.css('padding-top').slice(0, -2)
            + +self.target.css('padding-bottom').slice(0, -2)
            + +self.target.css('margin-top').slice(0, -2)
            + +self.target.css('margin-bottom').slice(0, -2));
          self.container.css('top', self.target.position().top);
          self.container.css('left', self.target.position().left);
        } catch(e) {
          console.log('an exception occured while resizing the loader');
        }
      } else {
        self.container.css('top', self.target.position().top + self.target.height()/2);
        self.container.css('left', self.target.position().left + self.target.width()/2 - self.loader.canvas.width/2);
      }
    }
  }

  self.resizeCSS3 = function() {
    if (self.is_blocking) {
      try {
        var width = self.target.width()
          + +self.target.css('padding-left').slice(0, -2)
          + +self.target.css('padding-right').slice(0, -2)
          + +self.target.css('margin-left').slice(0, -2)
          + +self.target.css('margin-right').slice(0, -2);
        var height = self.target.height()
          + +self.target.css('padding-top').slice(0, -2)
          + +self.target.css('padding-bottom').slice(0, -2)
          + +self.target.css('margin-top').slice(0, -2)
          + +self.target.css('margin-bottom').slice(0, -2);
        var top = self.target.position().top;
        var left = self.target.position().left;

        self.sheet.css('width', width);
        self.sheet.css('height', height);
        self.sheet.css('top', top);
        self.sheet.css('left', left);
      } catch(e) {
        console.log('an exception occured while resizing the loader');
      }
    } else {
      var top = self.target.position().top + self.target.height()/2;
      var left = self.target.position().left + self.target.width()/2;

      self.sheet.css('top', top);
      self.sheet.css('left', left);
    }
  }

  /**
   * Spawn a new instance and start it on the selector
   */
  self.spawn = function(selector) {
    if (self.errors) return null;
    var loader = new JSLoader(false);
    if (typeof(selector) === 'string') {
      loader.setTarget($(selector));
    } else {
      loader.setTarget(selector);
    }
    return loader;
  }

// hide the loading animation
  self.stop = function() {
    self.isStopped = true;
    if(supportsCSS3()) {
      if(self.container != null) {
        self.container.fadeOut(200, function() {
          $(this).remove();
        });
;     }
    } else {
      if (self.errors) return null;
      self.loader.stop();
      if (self.container != null) {
        self.container.fadeOut(200, function() {
          $(this).remove();
        });
      }
    }
  }

// enable/disable the overlay
  self.blocking = function(is_blocking) {
    var wasRunning = !self.isStopped;
    if(supportsCSS3()) {
      self.blockingCSS3(is_blocking);
    } else {
      if (self.errors) return null;
      var restart_please = false;
      self.stop();

      // clean up DOM
      if (self.container != null) {
        self.container.remove();
      }

      if (is_blocking) {
        self.is_blocking = true;
        // build blocking animation
        self.container = $('<div>').attr('id','js-loader-animation');
        var containerInner = $('<div>').attr('id','js-loader-inner');
        self.container.append(containerInner);
        containerInner.append(self.loader.canvas);
        self.loader.canvas.style.marginTop = -(self.loader.fullHeight) / 2 + 'px';
        self.loader.canvas.style.marginRight = -(self.loader.fullWidth) / 2 + 'px';
        self.container.fadeOut(0);
        containerInner.css('position', 'absolute');
        containerInner.css('top', '50%');
        containerInner.css('right', '50%');
        self.container.css('background-color', 'rgba(255,255,255,0.6)');
        self.container.css('width', '100%');
        self.container.css('height', '100%');
        if(self.isParent) {
          self.container.css('position', 'fixed');
        } else {
          self.container.css('position', 'absolute');
        }
        self.container.css('top', '0');
        self.container.css('right', '0');
        self.container.css('z-index', '1020');
      } else {
        self.is_blocking = false;
        // build non-blocking animation
        self.container = $('<div>').attr('id','js-loader-animation');
        self.container.append(self.loader.canvas);
        self.loader.canvas.style.marginTop = -(self.loader.fullHeight) / 2 + 'px';
        self.loader.canvas.style.marginRight = -(self.loader.fullWidth) / 2 + 'px';
        self.container.fadeOut(0);
        if(self.isParent) {
          self.container.css('position', 'fixed');
        } else {
          self.container.css('position', 'absolute');
        }
        self.container.css('bottom', '0');
        self.container.css('right', '0');
        self.container.css('z-index', '1020');
        self.container.css('width', '0');
        self.resize();
      }
    }
    // restart the animation
    if(wasRunning) self.start();
  }

  /**
   *
   * Enables or disables the overlay
   * @param is_blocking
   * @returns {null}
   */
  self.blockingCSS3 = function(is_blocking) {
    self.is_blocking = is_blocking;
    if(is_blocking) {
       self.container.addClass('blocking');
    } else {
       self.container.removeClass('blocking');
    }
  }
  __init();
}