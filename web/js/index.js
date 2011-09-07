$(document).ready(function() {
  
  var hudsonObject = new Hudson();
  hudsonObject.init();

  var basecampObject = new Basecamp(hudsonObject);
  basecampObject.init();

  var twitterObject = new Twitter();
  twitterObject.init();
});
