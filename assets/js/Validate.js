
function Validate(element) {
    var self = this;
    self.field = element;
    self.inputDelay = 0;
    self.inputTimeout = '';
    self.timeout = 300; // how long before validation occurs
    self.strict = true;
    self.group = false;
    self.submitBtn = new Array();
    __init();

    function __init() {
      self.field.addClass('validate-input');
      self.validation = self.field.data('validate');
      var quiet = self.field.attr('data-quiet');
      if (typeof quiet === 'undefined' || quiet === false) {
        self.quiet = false;
      } else {
        self.quiet = true;
      }

    // Prepare Group Validation
      if(self.field.attr('data-group')) {
        self.group = $('*[data-group=' + self.field.data('group') + ']');
        self.group.each(function() {
          $(this).data('original-value', $(this).val());
          if ($(this).attr('data-submit-btn')) {
            self.submitBtn.push($($(this).data('submit-btn')));
          }
        });
      } else {
        if (self.field.attr('data-submit-btn')) {
          self.submitBtn.push($(self.field.data('submit-btn')));
        }
      }

    // Initialize Validation
      var currentVal = self.field.val();
      self.originalValue = currentVal;
      self.field.data('last-value', currentVal);
      if (!self.field.data('strict')) {
        self.strict = false;
      }

    // Setup Events
      var fieldType = self.field[0].nodeName.toLowerCase();
      if(fieldType === 'input' ) {
        self.field.bind('keyup mouseup change', function() {
          var lastVal = self.field.data('last-value'), currentVal = self.field.val();
          self.inputDelay = 0;
          if (lastVal!=currentVal) self.delayedInput();
          self.field.data('last-value', currentVal);
        });
      } else if (fieldType === 'select') {
        self.field.change(function() {
          self.check();
        });
      } else {
        console.debug('Validation: Unsupported input element "' + fieldType + '"');
      }
    }

  // Check For Input Delay
    self.delayedInput = function() {
      clearTimeout(self.inputTimeout);
      if (self.inputDelay>self.timeout) {
        self.check();
        self.inputDelay = 0;
        return;
      }
      self.inputDelay = self.inputDelay+100;
      self.inputTimeout = setTimeout(self.delayedInput, 100);
    }

    self.escapeValues = function(val) {
      var escaped = val.replace(/\+/g, encodeURIComponent('+'));
      return escaped;
    }

  // Submit The Validation
    self.check = function() {
    // Prepare Values
      var values = '', newValue = undefined;
      var validatedField = self.field;
      if (self.group) {
        var numValues = 0, diff = false;
        validatedField = self.group;
        self.group.each(function(index, elem) {
          if ($(elem).val() != '') {
            numValues ++;
            values = values + '&' + $(elem).data('name') + '=' + self.escapeValues($(elem).val());
          // Look For Changes
            if ($(elem).val() != $(elem).data('original-value')) {
              diff = true;
            }
          }
        });
      // Do Not Validate If Group Is Incomplete Or There Are No Changes While In Strict Mode
        if (numValues != self.group.length || (!diff && !self.strict)) {
          values = false;
        }
      } else {
        newValue = self.field.val();
        values = '&value=' + self.escapeValues(self.field.val());
      }

    // Begin Validation
      validatedField.parent().children('div.validate-msg').remove();
      if(values && (newValue !=self.originalValue || self.strict)) {
      // Add Loading
        validatedField.each(function(index, elem) {
          var quiet = $(elem).attr('data-quiet');
          if (typeof quiet === 'undefined' || quiet === false) {
            $(elem).after('<div class="validate-msg alert alert-info">Validating</div>');
          }
        });
        if (global.lightbox) global.lightbox.resize(global.lightbox.width);
        $.get('/atomar/api/validate/?action=' + self.validation + values, function(data) {
          var success = true;

        // Remove Loading
          validatedField.parent().children('div.validate-msg').remove();
          var message = '';
        // Get Message
          if (self.group) {
          // Display On Multiple Fields
            if (data.status == -1) {
              console.debug(data);
              message = '<div class="validate-msg alert alert-danger" data-static="true">'+data.msg+'</div>';
              validatedField.each(function(index, elem) {
                var quiet = $(elem).attr('data-quiet');
                if (typeof quiet === 'undefined' || quiet === false) {
                  $(elem).after(message);
                }
              });
            } else {
              self.group.each(function(index, elem) {
                elemData = data[$(elem).data('name')];
                if (elemData.status == 1) {
                // Success
                  message = '<div class="validate-msg alert alert-success" data-static="true">'+elemData.msg+'</div>';
                } else if (elemData.status == 0) {
                // Failure
                  success = false;
                  message = '<div class="validate-msg alert alert-danger" data-static="true">'+elemData.msg+'</div>';
                } else if (elemData.status == -1) {
                // Error
                  console.debug(data);
                  message = '<div class="validate-msg alert alert-danger" data-static="true">'+elemData.msg+'</div>';
                } else if (elemData.status == -2) {
                // Warning
                  message = '<div class="validate-msg alert alert-warning" data-static="true">'+elemData.msg+'</div>';
                } else {
                // Unknown
                  console.debug(data);
                  message = '<div class="validate-msg alert alert-danger">Unknown Exception</div>';
                }
                var quiet = $(elem).attr('data-quiet');
                if (typeof quiet === 'undefined' || quiet === false) {
                  $(elem).after(message);
                }
              });
            }
          } else {
          // Display On Single Field
            if (data.status == 1) {
            // Success
              message = '<div class="validate-msg alert alert-success" data-static="true">'+data.msg+'</div>';
            } else if (data.status == 0) {
            // Failure
              success = false;
              message = '<div class="validate-msg alert alert-danger" data-static="true">'+data.msg+'</div>';
            } else if (data.status == -1) {
            // Error
              console.debug(data);
              message = '<div class="validate-msg alert alert-danger" data-static="true">'+data.msg+'</div>';
            } else if (data.status == -2) {
            // Warning
              message = '<div class="validate-msg alert alert-warning" data-static="true">'+data.msg+'</div>';
            } else {
            // Unknown
              console.debug(data);
              message = '<div class="validate-msg alert alert-danger" data-static="true">Unknown Exception</div>';
            }
            // display message
            if (!self.quiet) {
              validatedField.after(message);
            }
          }
          if (!success) {
            self.submitBtn.forEach(function(value) {
              $(value).attr('disabled', true);
            });
          } else if(self.isFormValid()) {
          // Enable Submit
            self.submitBtn.forEach(function(value) {
              $(value).attr('disabled', false);
            });
          }
        });
      }
      else if(self.isFormValid()){
      // Nothing To Validate
        self.submitBtn.forEach(function(value) {
          $(value).attr('disabled', false);
        });
        if (global.lightbox) global.lightbox.resize(global.lightbox.width);
      }
    }

    self.isFormValid = function() {
      return self.field.closest('form').find('.validate-failed').length == 0;
    }
  }