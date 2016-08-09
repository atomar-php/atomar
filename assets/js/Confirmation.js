function Confirmation(element) {
  var self = this;
  self.element = element;
  __init();

  /**
   * Initialize the confirmation
   */
  function __init() {
  // set up confirmation on click
    $(self.element).click(function(event) {
      var message = $(self.element).data('confirm');
      var notable = $(self.element).data('for');
      $(notable).addClass('pending-confirmation');
      var result = confirm(message);
      if(result) {
        // ok
        $(self.element).trigger('ok', event);
      } else {
        // cancel
        event.preventDefault();
        if($(self.element).data('loading-text')) {
          $(self.element).button('reset');
        }
        $(self.element).trigger('cancel', event);
      }
      $(notable).removeClass('pending-confirmation');
    });

    // set up triggers
    $(self.element).on('ok', function(event){
      if(self.okCallback) {
        self.okCallback(event);
      }
    });
    $(self.element).on('cancel', function(event){
      if(self.cancelCallback) {
        self.cancelCallback(event);
      }
    });
  }

  self.onCancel = function(fun) {
    self.cancelCallback = fun;
  }

  self.onOk = function(fun) {
    self.okCallback = fun;
  }
}