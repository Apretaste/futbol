// components container
var ui = {
  components: [],
  length: 0
};

// components definition
function button(properties) {
  if (typeof properties.id === "undefined") {
    properties.id = 'cmp' + (ui.length + 1);
  }

  ui[properties.id] = properties;

  var html = '<a id ="' + properties.id + '" href="#!" class="btn" ';
  if (typeof properties.send !== "undefined") {
    html += ' onclick="apretaste.send(ui.components[\'' + properties.id + '\']);">';
  }
  html += caption + '</a>';

  return html;
}

function row12(content, close) {
  if (typeof close === 'undefined') close = false;
  return '<div class="row"><div class="col l12 s12 m12 x12">' + content + (close ? '</div></div>' :'');
}

function h(level, text) {
  return row12('<h' + level + '>' + text + '</h' + level, true);
}

function h1(text) {
  return h(1, text);
}

function h2(text) {
  return h(2, text);
}

function tableHead(headers) {
  var html = '<table style="text-align:center" class="table"><tr>';
  for (var i = 0; i < headers.length; i++) {
    html += '<th>' + headers[i] + '</th>';
  }
  html += '</tr>';
  return row(html);
}