var global = {};
var startups = [];

// Parses JSON into an object
function fromJSON(json) {
  try {
    var obj = jQuery.parseJSON(json);
    return obj;
  } catch (err) {
    return false;
  }
}

// register global functions or variables.
function RegisterGlobal(name, obj) {
  global[name] = obj;
}

// register functions to be called durring startup.
function RegisterStartup(fun) {
  startups[startups.length] = fun;
}

// Encodes an object into JSON
function toJSON(obj) {
  try {
    var json = JSON.stringify(obj);
    return json;
  } catch (err) {
    return false;
  }
}

//  Saves an object into the local browser storage or in the global variable as a fallback
function variableSet(name, value) {
  if (typeof(Storage) !== 'undefined') {
    // local storage
    if (typeof value === 'undefined') {
      localStorage.removeItem(name);
    } else {
      localStorage.setItem(name, toJSON(value));
    }
    return true;
  } else {
    // fall back to global variable storage
    if (typeof value === 'undefined') {
      delete global[name];
    } else {
      global[name] = value;
    }
    return true;
  }
}

// Retrieves an object from the local browser storage or in the global variable as a fallback.
function variableGet(name, defaultValue) {
  if (typeof(Storage) !== 'undefined') {
    // local storage
    var data = localStorage.getItem(name);
    if (data != null) {
      return fromJSON(data);
    } else if (typeof defaultValue !== 'undefined') {
      localStorage.setItem(name, defaultValue);
      return defaultValue;
    } else {
      return null;
    }
  } else {
    // fall back to global variable storage
    if (name in global) {
      return global[name];
    } else if (typeof defaultValue !== 'undefined') {
      global[name] = defaultValue;
      return defaultValue;
    } else {
      return null;
    }
  }
}

// performs the lightbox resizing when a chosen.jquery element is expanded
function chosenResizeLightbox(chosen) {
  if (global.lightbox) {
    var widget_height = chosen.container.outerHeight(true) + chosen.dropdown.outerHeight(true)
      , widget_position = chosen.container.offset();
    var lightbox_overflow_height = widget_height + widget_position.top + 20;
    if (lightbox_overflow_height > global.lightbox.height) {
      global.lightbox.resize(global.lightbox.width, lightbox_overflow_height);
    }
  }
}

function letterTrim(text, length) {
  if (text.length > length) {
    return text.substring(0,length-3)+'...';
  } else {
    return text;
  }
}



// Utility function to add a new inline edit form and include
// it in the global array.
// @deprecated
function addToInlineEdit(element) {
  var form = new InlineEdit(element);
  // InlineForms[form.id] = form;
}

// function executeFunctionByName(functionName, context /*, args */) {
//   var args = Array.prototype.slice.call(arguments).splice(2);
//   var namespaces = functionName.split(".");
//   var func = namespaces.pop();
//   for(var i = 0; i < namespaces.length; i++) {
//     context = context[namespaces[i]];
//   }
//   return context[func].apply(this, args);
// }

// utility method to set up validation on a field
// function validate(elements, validation, success, failure, error) {
//   success = success || 'Input is valid';
//   failure = failure || 'Input is invalid';
//   error = error || 'Unexpected input';

//   elements.blur(function() {
//     var field = $(this);
//     field.parent().children('div.validate').remove();
//     var field_val = field.val();
//     if(field_val!='') {
//       // add loading
//       field.after('<div class="validate alert alert-info validate-is-checking">Validating</div>');
//       $.post('/atomar/api/' + validation, {
//         value:field_val
//       }, function(data) {
//         // remove loading
//         field.parent().children('div.validate').remove();
//         var message = '';
//         // get message
//         if (data=='1') {
//           message = '<div class="validate alert alert-success">'+success+'</div>';
//         } else if (data=='0') {
//           message = '<div class="validate alert alert-error">'+failure+'</div>';
//         } else {
//           message = '<div class="validate alert alert-error">'+error+'</div>';
//         }
//         // display message
//         field.after(message);
//       });
//     }
//     return false;
//   });
// }

// utility method to set up an automatic dismiss delay on alerts.
function delay_alert(elements, delay, delta) {
  var time_delay = delay + delta * elements.length;
  elements.each(function(index) {
    var myalert = $(this);
    // add a slight delay between each of the alerts
    if(index > 0) {
      time_delay -= delta;
    }
    setTimeout(function() {
      myalert.alert('close');
    }, time_delay);
  }).bind('click', function() {
    $(this).alert('close');
  });
}

function delay(callback, delay) {
  var time_delay = delay;
  setTimeout(function() {
    callback();
  });
}

// close all of the alerts
function close_alerts() {
  $('.alert[data-static!="true"]').alert('close');
}

function set_error(message) {
  var closeBtn = '';
  if(!global.lightbox) {closeBtn = '<button type="button" class="close" data-dismiss="alert">&times;</button>';}
  $('.growl').prepend('<div class="alert alert-danger fade in">'+closeBtn+'<strong>Error!</strong> '+message+'</div>');
  $('.alert').alert();
  $('.alert-danger').bind('click', function() {
    $(this).alert('close');
  });
}

function set_warning(message) {
  var closeBtn = '';
  if(!global.lightbox) {closeBtn = '<button type="button" class="close" data-dismiss="alert">&times;</button>';}
  $('.growl').prepend('<div class="alert alert-warning fade in">'+closeBtn+'<strong>Warning!</strong> '+message+'</div>');
  $(".alert").alert();
  delay_alert($('.alert-warning'), 5000, 100);
}

function set_success(message) {
  var closeBtn = '';
  if(!global.lightbox) {closeBtn = '<button type="button" class="close" data-dismiss="alert">&times;</button>';}
  $('.growl').prepend('<div class="alert alert-success fade in">'+closeBtn+'<strong>Success!</strong> '+message+'</div>');
  $(".alert").alert();
  delay_alert($('.alert-success'), 5000, 100);
}

function set_notice(message) {
  var closeBtn = '';
  if(!global.lightbox) {closeBtn = '<button type="button" class="close" data-dismiss="alert">&times;</button>';}
  $('.growl').prepend('<div class="alert alert-info fade in">'+closeBtn+'<strong>Notice:</strong> '+message+'</div>');
  $(".alert").alert();
  delay_alert($('.alert-info'), 6000, 100);
}

function strtodate(string) {
  if (string === null) {
    return null;
  }
  try {
  var d = Date.parse(string);
  var parts = string.split(' ');
  var date_parts = parts[0].split('-');
  var time_parts = parts[1].split(':');
  var date = new Date(parseInt(date_parts[0], 10),     // year
                      parseInt(date_parts[1], 10) - 1, // month, starts with 0
                      parseInt(date_parts[2], 10),     // day
                      parseInt(time_parts[0], 10),     // hours
                      parseInt(time_parts[1], 10),     // minutes
                      parseInt(time_parts[2], 10));    // seconds
  } catch (e) {
    console.debug(e);
    console.debug(string);
    return null;
  }
  return date;
}

// format dates for use in date forms
function form_date(date) {
  try {
    var curr_date = date.getDate();
    var curr_month = date.getMonth(); // Months are zero based
    var curr_year = date.getFullYear();
    var curr_hour = date.getHours();
    var curr_minute = date.getMinutes();
    var a = 'AM';
    if (curr_hour > 12) {
      a = 'PM';
      curr_hour -= 12;
    }
    if (curr_minute < 10) {
      curr_minute = '0' + curr_minute;
    }
    if (curr_hour == 0) {
      curr_hour = 12; // for 12 am
    }
    if (curr_hour < 10) {
      curr_hour = '0' + curr_hour;
    }
    return curr_date + '/' + (curr_month + 1) + '/' + curr_year + ' ' + curr_hour + ':' + curr_minute + ' ' + a;
  } catch(e) {
    return '';
  }
}

function db_date(date) {
  try {
    var curr_date = date.getDate();
    var curr_month = date.getMonth() + 1; // Months are zero based
    var curr_year = date.getFullYear();
    var curr_hour = date.getHours();
    var curr_minute = date.getMinutes();
    if (curr_minute < 10) {
      curr_minute = '0' + curr_minute;
    }
    if (curr_hour < 10) {
      curr_hour = '0' + curr_hour;
    }
    if (curr_month < 10) {
      curr_month = '0' + curr_month;
    }
    return curr_year + '-' + curr_month + '-' + curr_date + ' ' + curr_hour + ':' + curr_minute + ':' + ':00';
  } catch(e) {
    return '0000-00-00 00:00:00';
  }
}

function fancy_date(date) {
  date  = date || new Date();
  try {
    var curr_date = date.getDate();
    var curr_month = date.getMonth(); // Months are zero based
    var curr_year = date.getFullYear();
    var curr_hour = date.getHours();
    var curr_minute = date.getMinutes();
    var a = 'AM';
    if (curr_hour > 12) {
      a = 'PM';
      curr_hour -= 12;
    }
    if (curr_minute < 10) {
      curr_minute = '0' + curr_minute;
    }
    if (curr_hour == 0) {
      curr_hour = 12; // for 12 am
    }
    if (curr_hour < 10) {
      curr_hour = '0' + curr_hour;
    }
    return month(curr_month) + ' ' + day_position(curr_date) + ' ' + curr_year + ' at ' + curr_hour + ':' + curr_minute + ' ' + a;
  } catch(e) {
    return '';
  }
}

function simple_date(date) {
  try {
    var curr_date = date.getDate();
    var curr_month = date.getMonth(); // Months are zero based
    var curr_year = date.getFullYear();
    return month(curr_month) + ' ' + day_position(curr_date) + ' ' + curr_year;
  } catch(e) {
    return '';
  }
}

function fancy_time(seconds) {
  var h = Math.floor(seconds/3600);
  seconds -= h*3600;
  var m = Math.floor(seconds/60);
  seconds -= m*60;
  return (h < 10 ? '0'+h : h)+":"+(m < 10 ? '0'+m : m)+":"+(seconds < 10 ? '0'+seconds : seconds);
}

function day_position(day) {
  var day = day+'';
  var format = '';
  switch(day.charAt(day.length-1)) {
    case '1':
      format = 'st';
      break;
    case '2':
      format = 'nd';
      break;
    case '3':
      format = 'rd';
      break;
    default:
      format = 'th';
  }
  return day + format;
}

function month(index) {
  var month = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec'
  ];
  return month[index];
}

// change the shade of a color
// http://stackoverflow.com/questions/5560248/programmatically-lighten-or-darken-a-hex-color
// valid values for percent are -1.0 to 1.0
function shade_color(color, percent) {   
    var f=parseInt(color.slice(1),16),t=percent<0?0:255,p=percent<0?percent*-1:percent,R=f>>16,G=f>>8&0x00FF,B=f&0x0000FF;
    return "#"+(0x1000000+(Math.round((t-R)*p)+R)*0x10000+(Math.round((t-G)*p)+G)*0x100+(Math.round((t-B)*p)+B)).toString(16).slice(1);
}

// blends two colors together. Valid values for p are 0.0 to 1.0.
function blend_colors(c0, c1, p) {
    var f=parseInt(c0.slice(1),16),t=parseInt(c1.slice(1),16),R1=f>>16,G1=f>>8&0x00FF,B1=f&0x0000FF,R2=t>>16,G2=t>>8&0x00FF,B2=t&0x0000FF;
    return "#"+(0x1000000+(Math.round((R2-R1)*p)+R1)*0x10000+(Math.round((G2-G1)*p)+G1)*0x100+(Math.round((B2-B1)*p)+B1)).toString(16).slice(1);
}


// convert rgb css color to hex
// http://stackoverflow.com/questions/1740700/how-to-get-hex-color-value-rather-than-rgb-value
function rgb2hex(rgb) {
  rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
  function hex(x) {
      return ("0" + parseInt(x).toString(16)).slice(-2);
  }
  return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}

// http://stackoverflow.com/questions/2919337/jquery-convert-line-breaks-to-br-nl2br-equivalent
function nl2br(str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}
function br2nl(str, is_xhtml) {
  if(is_xhtml || typeof is_xhtml === 'undefined') {
    return str.replace(/<br \/>/g, '\r');
  } else {
    return str.replace(/<br>/g, '\r');
  }
}
// function pulse(element) {
//   console.debug(element);
//   var original = $(element).css("border-left-color");
//   $(element).animate({
//       borderColor: 'red'
//     }, 500, 'swing', function() {
//       $(element).animate({
//           borderColor: original
//         }, 500, 'swing'
//       );
//     }
//   );
// }
function human_to_machine(human_name, separator) {
  separator = separator || '-';
  var machine_name, tokens = [
    [/[^a-zA-Z0-9]+/g, separator],
    [/-+/g, separator],
    [/^-+/g, ''],
    [/-+$/g, '']
  ];
  machine_name = human_name;
  for (var i = 0; i < tokens.length; i++) {
    machine_name = machine_name.replace(tokens[i][0], tokens[i][1]);
  }
  return machine_name.toLowerCase();
}

// TODO: this is the new name. Get rid of the old one.
function human2machine(human_string, separator) {
  return human_to_machine(human_string, separator);
}

// pad a string (often a number) with a leading character
function pad(number, character, length) {
  var str = '' + number;
  while (str.length < length) {
    str = character + str;
  }
  return str;
}

Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
  var n = this,
  decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces,
  decSeparator = decSeparator == undefined ? "." : decSeparator,
  thouSeparator = thouSeparator == undefined ? "," : thouSeparator,
  sign = n < 0 ? "-" : "",
  i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
  j = (j = i.length) > 3 ? j % 3 : 0;
  return sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : "");
};

// http://stackoverflow.com/questions/486896/adding-a-parameter-to-the-url-with-javascript
function parameterizeUrl(url, key, value)
{
  key = encodeURI(key); value = encodeURI(value);
  var kvp = url.split('&')
    , new_url;
  var i=kvp.length; var x; while(i--) 
  {
    x = kvp[i].split('=');
    if (x[0]==key)
    {
      x[1] = value;
      kvp[i] = x.join('=');
      break;
    }
  }
  if(i<0) {kvp[kvp.length] = [key,value].join('=');}
  if(url.split('?').length == 1) {
    new_url =  kvp.join('?');
  } else {
    new_url = kvp.join('&');
  }
  return new_url;
}
