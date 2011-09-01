$(document).ready(function(){

  loadBasecampInfos();
  loadHudsonFails();
  loadTwitts();

  setInterval(loadBasecampInfos, 5 * 60 * 1000);
  setInterval(loadHudsonFails, 2 * 60 * 1000);
  setInterval(loadTwitts, 2 * 60 * 1000);

  function loadBasecampInfos() {
    $.ajax({
      url: 'loadBasecampInfos.php',
      success: function(html){
        loadHudsonFails();
        $('#basecampContent').html(html);
      }
    });
  }
  
  function loadHudsonFails() {
    $.ajax({
      url: 'loadHudsonFails.php',
      success: function(json){
        var projects = jQuery.parseJSON(json);
        $.each(projects, function(index, value) { 
          if($('#' + index) != undefined) {
            if(value==0) {
              $('#' + index).removeClass('hasFailedJobs');
            } else {
              $('#' + index).addClass('hasFailedJobs');
            }
          }
        });
      }
    });
  }
  
  function loadTwitts() {
    $.ajax({
      url: 'loadTwitts.php',
      success: function(html){
        $('#twitterContent').html(html);
      }
    });
  }
})
