
// Document Ready
$(document).ready(function () {

// automatically dismiss alerts
  delay_alert($('.alert-success[data-static!="true"]'), 5000, 100);
  delay_alert($('.alert-info[data-static!="true"]'), 6000, 100);
  delay_alert($('.alert-warning[data-static!="true"]'), 7000, 100);
  $('.alert-danger[data-static!="true"]').bind('click', function() {
    $(this).alert('close');
  });

// enable field validation
  $('*[data-validate]').each(function() {
    var v = new Validate($(this));
  });

  //validate($('.validate-username'), 'validate_username', 'Username is available', 'Username is not available');

// check if the email exists
  //validate($('.validate-email'), 'validate_email', 'Email is available', 'Email is not available', 'Invalid email address');

  // enable tool tips
  $('.tip').tooltip();

  // enable popovers
  $('.pop').popover();

  // enable auto modals
  $('.auto-modal').modal('show');

  // loading buttons
  $('.btn[data-loading-text]').on('click', function(e) {
    $(this).button('loading');
  });

  // allow support for selecting from one chosen element to another
  var chosen_state = 0;

  // enable custom select fields
  if ($(".chzn-select").chosen) {
    $(".chzn-select").chosen({
      width:'100%'
    }).on('chosen:showing_dropdown', function(evt, obj) {
      if (global.lightbox) chosen_state ++;
      chosenResizeLightbox(obj['chosen']);
    }).on('chosen:hiding_dropdown', function(obj) {
      if (global.lightbox) chosen_state --;
      if (global.lightbox && chosen_state == 0) global.lightbox.resize(global.lightbox.width);
    });
    $(".chzn-select-deselect").chosen({
      allow_single_deselect:true,
      width: '100%'
    }).on('chosen:showing_dropdown', function(evt, obj) {
      if (global.lightbox) chosen_state ++;
      chosenResizeLightbox(obj['chosen']);
    }).on('chosen:hiding_dropdown', function(obj) {
      if (global.lightbox) chosen_state --;
      if (global.lightbox && chosen_state == 0) global.lightbox.resize(global.lightbox.width);
    });
  }
  // special handling for collapsing elemeents
  $(window).on('shown.bs.collapse', function(e) {
    if (global.lightbox) {
      global.lightbox.resize(global.lightbox.width);
    }
  }).on('hidden.bs.collapse', function(e) {
    if (global.lightbox) {
      global.lightbox.resize(global.lightbox.width);
    }
  });

  // bind inline edits
  $('[data-edit-id]').each(function(index, element) {
    addToInlineEdit(element);
  });

  // enable confirmation messages
  $('*[data-confirm]').each(function(index, element) {
    var confirm = new Confirmation(element);
    // prepare callback
    var callback = $.trim($(element).data('callback'));
    var args = $.trim($(element).data('callback-args'));
    if (callback) {
      confirm.onOk(function(event) {
        window[callback](event, args);
      });
    }
  });

  // initialize file inputs
  $('input[type=file]').bootstrapFileInput();

  // initialize lightboxes
  $('[data-lightbox]').each(function() {
    if ($(this).data('lightbox') != '') {
      var lightbox = new Lightbox($(this).data('lightbox'), $(this));
    } else {
      console.debug('Deprecated use of lightbox! use data-lightbox="'+$(this).attr('href')+'" instead of href="'+$(this).attr('href')+'"');
      var lightbox = new Lightbox($(this).attr('href'), $(this));
    }
  });

  // create a new global loader object
  var loader = new JSLoader();
  RegisterGlobal('loader', loader);

  // do not allow startup definitions to be added after startup has finished
  RegisterStartup = function(fun) {
    console.debug('RegisterStartup: definitions may not be created after startup. ' + fun);
  }

  // execute startup functions
  for(var i=0; i<startups.length; i ++) {
    startups[i]();
  }
});

// Document Keyup
$(document).bind('keyup', function(e) {
  if (e.keyCode == 27) {
    $(window).trigger('key.escape', e)
    // hide all tooltips and popovers on escape
    $('.tip').tooltip('hide')
    $('.pop').popover('hide')
    close_alerts()
  } else if(e.keyCode == 13) {
    $(window).trigger('key.return', e)
  } else if(e.keyCode == 32) {
    $(window).trigger('key.space', e)
  }
});

