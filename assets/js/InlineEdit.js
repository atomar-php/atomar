function InlineEdit(element) {
  var self = this;
  if (!InlineEdit.id) {
    InlineEdit.id = 1;
  } else {
    InlineEdit.id ++;
  }

  self.$element = $(element);
  self.$form = null;

  // self.fields = new Array();
  self.id = InlineEdit.id;
  self.previousValue = null;
  self.editBtn = null;
  self.isOpen = false;
  self.isSaving = false;
  __init();

  /**
   *  Initialize the inline form
   */
  function __init() {
    self.$element.addClass('inline-edit-element');
    self.$element.data('form_id', self.id);

    // collect field information
    self.modelFieldID = self.$element.data('edit-id');
    self.modelField = self.$element.data('edit-field');
    self.model = self.$element.data('edit-model');
    self.module = self.$element.data('edit-module');
    self.fieldType = self.$element.data('edit-type');
    self.modelFieldKey = self.$element.data('edit-key');
    self.previousValue = self.$element.html();


    // validate parameters
    if (!self.modelFieldID) {
      console.debug('InlineEdit: missing model field id');
      return false
    }

    // TODO: eventually we will combine this along with the commented code below to generate the form for different field types.

    // if(self.fieldType == 'select') {
    //   var url = '/atomar/api/get_key_value_'+self.modelField;
    //   if (self.module) {
    //     url = '/atomar/api/'+self.module+'/get_key_value_'+self.modelField;
    //   }
    //   // submit data
    //   $.get(url, function(data) {
    //     // if the key was provided, then only display it's value
    //     try {
    //       var options = $.parseJSON(data);
    //     } catch (err) {
    //       console.debug('Inline edit error...');
    //       console.debug(data);
    //     }
    //     self.selectOptions = options;
    //   });
    // }

    // build form
    self.$input = $('<input/>')
      .attr('type','text')
      .attr('name', self.modelField)
      .attr('value', self.$element.html().replace(/"/g, '&quot;'))
      .attr('autocomplete', 'off')
      .addClass('inline-edit-input input-sm form-control');
    self.$ok = $('<span/>').addClass('inline-edit-ok glyphicon glyphicon-ok text-success tip').attr('title', 'accept');
    self.$cancel = $('<span/>').addClass('inline-edit-cancel glyphicon glyphicon-remove text-danger tip').attr('title', 'cancel');
    self.$notice = $('<div/>').addClass('inline-edit-notice pull-left text-muted');
    self.$form = $('<div/>').addClass('inline-edit-form')
      .append(self.$input)
      .append(self.$notice)
      .append($('<div/>').addClass('inline-edit-controls pull-right')
        .append(self.$ok)
        .append(self.$cancel)
      );

    // display form on hover
    self.$element.parent().hover(function() {
      self.open();
    }, function() {
      if (!self.$input.is(':focus')) {
        self.close(true);
      }
    });

    // set up edit button
    self.editBtn = $('[data-edit="#'+self.$element.attr('id')+'"]').on('click', function(e) {
      e.preventDefault();
      if(self.isOpen) {
        self.close(false);
      } else {
        self.open(true);
      }
    });

    // close all the inline edit fields when the escape key is pressed
    $('body').on('key.escape', function(e) {
      self.close(false);
    });
  }

  /**
   *  Open the inline form
   */
  self.open = function(focus) {
    focus = focus || false
    if (self.isOpen) return
    self.isOpen = true

    if (self.previousValue != self.$input.val()) {
      if (!self.hasErrors) self.$notice.html('pending changes...');
    } else {
      self.$notice.html('');
    }

    // we need to set some styles to correctly position the form inside
    self.$element.parent().addClass('inline-edit-container');

    // TODO: this code should eventually be placed in the init to generate the form.

    // // select field
    // if (self.selectOptions) {
    //   var options = '';
    //   for (var i = 0; i < self.selectOptions.length; i++) {
    //     var selected = '';
    //     if (self.selectOptions[i].key == fieldObj.data('key')) {
    //       selected = 'selected';
    //     }
    //     options += '<option value="'+self.selectOptions[i].key+'" '+selected+'>'+self.selectOptions[i].value+'</option>';
    //   }
    //   fieldObj.html('<select data-placeholder="Choose a '+fieldObj.data('field')+'..." class="chzn-select inline-edit-input form-control" name="'+fieldObj.data('field')+'"><option></option>'+options+'</select>');
    //   // initialize chosen
    //   fieldObj.find('.chzn-select').chosen();
    //   // focus on the first field
    //   if(index == 0) {
    //     var input = $(fieldObj.find(':input'));
    //     input.focus();;
    //     // input.addClass('span12');
    //   }
    // } else {
    //   // text area
    //   if (self.fieldType == 'textarea') {
    //     // input field
    //     self.$element.html('<textarea class="inline-edit-input form-control" rows="5" name="'+self.modelField+'" >'+br2nl(self.$element.html().replace(/"/g, '&quot;'), false)+'</textarea><span class="muted inline-help">ctrl+return to submit</span>');
    //   } else if(self.fieldType == 'date') {
    //     // date field
    //     var datePicker = $('<div></div>').attr({
    //       'class': 'input-group date pull-right col-md-4 col-lg-4'
    //     }).css('margin-bottom','5px').append(
    //       $('<input></input>').attr({
    //         'data-format':'dd/MM/yyyy HH:mm PP',
    //         type:'text',
    //         placeholder:'due date...',
    //         name:fieldObj.data('field'),
    //         value:fieldObj.data('field-value'),
    //         'class':'inline-edit-input form-control'
    //       }),
    //       $('<span></span>').attr({
    //         'class':'input-group-addon',
    //       }).append(
    //         $('<span></span>').attr({
    //           'data-time-icon':'glyphicon glyphicon-time',
    //           'data-date-icon':'glyphicon glyphicon-calendar'
    //         })
    //       )
    //     ).datetimepicker({
    //       startDate: new Date(),
    //       pick12HourFormat:true,
    //       maskInput:true,
    //       pickSeconds:false
    //     });
    //     fieldObj.html('');
    //     fieldObj.append(datePicker);
    //   } else {
    //     // input field
    //     self.$element.replaceWith(self.$form);
    //   }
    //   // focus on the input
    //   if (focus) self.$input.focus()
    // }

    self.$element.replaceWith(self.$form);

    // focus on the input
    if (focus) self.$input.focus()

    // add custom classes
    if(self.$element.attr('data-class')) {
      self.$element.find('.inline-edit-input').addClass(self.$element.data('class'));
    }

    // accept changes
    self.$ok.on('click', function(e) {
      self.submit();
    });

    // reject changes
    self.$cancel.on('click', function(e) {
      self.close(false);
    });

    // close the form when it loses focus and is not hovered
    self.$input.on('focusout', function(e) {
      if (!self.$form.is(':hover')) self.close(true);
    });

    self.$form.find('.tip').tooltip();

    // set up change events for input
    self.$input.on('blur keyup paste input change', function() {
      self.hasErrors = false;
      if (self.previousValue != self.$input.val()) {
        self.$notice.html('pending changes...');
      } else {
        self.$notice.html('');
      }
      return $(this);
    });

    return true;
  };

  /**
   *  Close the inline form
   */
  self.close = function(keepinput) {
    keepinput = keepinput || false;
    if (!self.isOpen || self.isSaving) return;
    self.isOpen = false;

    self.$form.replaceWith(self.$element);
    if (!keepinput) self.$input.val(self.previousValue);

    self.$element.parent().removeClass('inline-edit-container');

    // $(self.fields).each(function(index, field) {
    //   var fieldObj = field;
    //   if (fieldObj.selectOptions) {
    //     // select
    //     if(keepinput) {
    //       fieldObj.find('.inline-edit-input').each(function(index, input) {
    //         fieldObj.html($(input).find('option[value="'+$(input).val()+'"]').text());
    //         fieldObj.data('key', $(input).val());
    //       });
    //     } else {
    //       fieldObj.html(fieldObj.data('old-value'));
    //     }
    //   } else if(fieldObj.data('field-type') == 'date') {
    //     if (keepinput) {
    //       // get the new date
    //       var end_date = '', end_date_object = null;
    //       if (fieldObj.find('.inline-edit-input').val() != '' && fieldObj.find('.inline-edit-input').val() != undefined) {
    //         end_date_object = fieldObj.find('.date').data('datetimepicker').getLocalDate();
    //         fieldObj.html('Due '+fancy_date(end_date_object));
    //         fieldObj.data('field-value',form_date(end_date_object));
    //       } else {
    //         fieldObj.html('');
    //         fieldObj.data('field-value','');
    //       }
    //       fieldObj.trigger('saved');
    //     } else {
    //       fieldObj.html(fieldObj.data('old-value'));
    //     }
    //   } else {
    //     // input
    //     if(keepinput) {
    //       fieldObj.find('.inline-edit-input').each(function(index, input) {
    //         var value = $(input).val();
    //         if ($(input).is('textarea')) {
    //           value = nl2br(value);
    //         }
    //         fieldObj.html(value);
    //         fieldObj.trigger('saved');
    //       });
    //     } else {
    //       fieldObj.html(fieldObj.data('old-value'));
    //     }
    //   }
    //   fieldObj.removeClass('inline-edit-field-active');
    //   fieldObj.removeData('old-value');
    //   // re-enable links
    //   if (fieldObj.is('a')) {
    //     fieldObj.unbind('click');
    //   }
    // });
    return true;
  };

  /**
   *  Submit the inline form
   */
  self.submit = function() {
    if (self.isSaving) return
    self.isSaving = true
    self.$input.attr('disabled', true)
    var callback = $.trim(self.$element.data('callback'))
    var callback_args = $.trim(self.$element.data('callback-args'))
    var payload = {}

    var value = self.$input.val()

    if (self.fieldType == 'textarea') {
      value = nl2br(value)
    } else if (self.fieldType == 'date') {
      var end_date = '', end_date_object = null
      if (value != '' && value != undefined) {
        end_date_object = self.$input.data('datetimepicker').getLocalDate()
        // :TRICKY: jl [YYY-MM-DD]: remove trailing pacific time.
        end_date = db_date(end_date_object)
      }
      value = end_date
    }

    payload.data = {}
    payload.data.key = self.modelField
    payload.data.value = value
    payload.data.id = self.modelFieldID
    payload.model = self.model

    // send to system or module
    var url = '/atomar/api/inline_edit'
    if (self.module)  url = '/atomar/api/'+self.module+'/edit_'+self.model

    // submit data
    $.post(url, payload, function(response) {
      self.isSaving = false
      self.$input.attr('disabled', false)
      if(response && response.status == 'ok') {
        close_alerts();
        set_success('The changes were saved');
        self.previousValue = self.$input.val();
        self.$element.html(self.previousValue);
        self.close(true)

        // fire callback
        if (callback && typeof callback === 'function') {
          var args = callback_args.split(',')
          window[callback](response, payload, args)
        }
      } else {
        console.debug(url)
        console.debug(payload)
        console.debug(response)
        self.hasErrors = true
        close_alerts()
        set_error('The changes could not be saved');
        self.$notice.html('error saving changes...')
      }
    });
    return true;
  }
}