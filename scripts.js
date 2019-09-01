function pad(n, width, z) {
  z = z || '0';
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

function formatDate(dateStr) {
  var date = new Date(dateStr);
  var year = date.getFullYear();
  var month = pad(1 + date.getMonth(),2);
  var day = pad(date.getDay(),2);
  var hour = (date.getHours() < 12) ? date.getHours() : date.getHours() - 12;
  var minutes = date.getMinutes();
  var amOrPm = (date.getHours() < 12) ? "am" : "pm";

  return day + '/' + month + '/' + year + ' ' + hour + ':' + minutes + amOrPm;
}
