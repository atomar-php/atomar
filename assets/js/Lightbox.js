function Lightbox(source, trigger) {
  var self = this;
  if (!Lightbox.id) {
    Lightbox.id = 1;
  } else {
    Lightbox.id ++;
  }
  self.id = Lightbox.id;
  self.url = source;
  self.trigger = $(trigger);
  self.box = null;
  self.is_resizing = false;
  self.dimensions = {height:400,width:600}; // default dimensions
  self.resize_time = 200;
  self.entrance_time = 200;
  __init();

  function __init() {
    // load the lightbox
    self.trigger.bind('click', function(e) {
      e.preventDefault();
      global.loader.start();
      self.load();
    });

    // catch events from the lightbox
    $(document).bind('lightbox.dismiss', function(e, data) {
      if (data.id == self.id) {
        global.loader.stop();
        if (self.box) {
          // dismiss
          $(self.box).animate({
            top: '-'+self.box.height()+'px',
            opacity: '0'
          }, self.entrance_time, function() {
            self.box.modal('hide');
            $('#lightbox-wrap-'+self.id).removeClass('on');
          });
          // fire callback
          if (data.callback != '') {
            if (typeof(data.callback) === 'string') eval(data.callback);
            else data.callback();
          }
        }
      }
    });
    $(document).bind('lightbox.redirect', function(e, data) {
      if (data.id == self.id) {
        if (self.box) {
          self.box.modal('hide');
          $('#lightbox-wrap-'+self.id).removeClass('on');
          if (data.url != '') {
            window.location.replace(data.url);
            window.location.reload(true);
          } else {
            window.location.reload(true);
          }
        }
      }
    });

    // Wait for lightbox to initialize itself
    // this will be triggered by the lightbox when it is ready
    $(document).bind('lightbox.init', function(e, data) {
      if (self.id == data.id) {
        // resize to initial dimensions
        self.dimensions = data.dimensions;
        if (!self.box.is(":visible")) {
          self.resize();
        }

        global.loader.stop();
        
        $('#lightbox-wrap-'+self.id).addClass('on');

        // animate lightbox entrance if it is new
        if (!self.box.is(":visible")) {
          $(self.box).css({
            top:'-'+self.box.height()+'px',
            opacity:'0'
          });
          self.box.modal('show');
          $(self.box).animate({
            top: '30px',
            opacity: '1'
          }, self.entrance_time);
        }
        // let the lightbox know we have connected.
        self.box[0].contentWindow.global.lightbox.acknowledge();
      }
    });

    $(document).bind('lightbox.resize', function(e, data) {
      if (data.id == self.id) {
        self.dimensions = data.dimensions;
        self.resize(true);
      }
    });

    // catch window events
    $(window).resize(function() {
      if (!self.is_resizing) {
        self.resize();
      }
    })
  }

  self.resize = function(animate) {
    self.is_resizing = true;
    animate = animate || false;
    if (self.dimensions && self.box) {
      var offset = ($(window).width() - self.dimensions.width)/2.0 + 'px';

      if (animate) {
        $(self.box).animate({
          height: self.dimensions.height +'px',
          width: self.dimensions.width + 'px',
          left: offset
        }, self.resize_time, function() {
          self.is_resizing = false;
        });
      } else {
        self.box.height(self.dimensions.height +'px');
        self.box.width(self.dimensions.width + 'px');
        $(self.box).css('left',offset);
        self.is_resizing = false;
      }
    }
  }

  self.load = function() {
    // remove old lightboxes
    $('#lightbox-wrap-'+self.id).remove();

    // pass id to lightbox
    var url = parameterizeUrl(self.url, '_lightbox_id', self.id);
    
    // get new lightbox
    $.ajax({
      type: 'get',
      url: url,
      async: true,
      cache: false,
      success: function(data) {
        $('body').append('<div id="lightbox-wrap-'+self.id+'" class="lightbox-wrap"><iframe src="'+url+'" id="lightbox-'+self.id+'" class="modal" width="'+self.width+'px" height="'+self.height+'px" tabindex="-1" role="dialog" aria-hidden="true" aria-labeledby="lightbox" >'+data+'</iframe></div>');
        self.box = $('#lightbox-'+self.id);
        self.box.modal({
          'backdrop':true, // show a dark backdrop
          'show':false, // display the lightbox
          'keyboard':false // dismiss the lightbox on esc
        });
      }
    });
  }
}