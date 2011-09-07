function Twitter() {

  this.TWITTER_URL = 'loadTwitts.php';
  this.TWITTER_DOM = $('#tweets');
  this.TWITTER_TIMER = null;
  this.OPTIONS = {
    timeToTick: 5 * 60 * 1000
  };

  this.init = function() {
    var $this = this;

    $this.loadInformations();

    $this.TWITTER_TIMER = setInterval(function() {$this.loadInformations()}, this.OPTIONS.timeToTick);
  };

  this.loadInformations = function() {
    var $this = this;

    $.ajax({
      url: $this.TWITTER_URL,
      success: function(html) {
        $this.TWITTER_DOM.html(html);
      }
    });
  };
}