function Basecamp(hudson) {
  
  this.BASECAMP_URL = 'loadBasecampInfos.php';
  this.BASECAMP_DOM = $('#projects');
  this.BASECAMP_TIMER = null;
  this.BASECAMP_BUG_TIMER = null;
  this.BASECAMP_BUG_CLASSNAME = 'bug';
  this.OPTIONS = {
    timeToTick: 10 * 60 * 1000
  };

  this.init = function() {
    var $this = this;

    $this.loadInformations();

    $this.BASECAMP_TIMER = setInterval(function() {$this.loadInformations()}, this.OPTIONS.timeToTick);
  };

  this.loadInformations = function() {
    var $this = this;

    $.ajax({
      url: $this.BASECAMP_URL,
      success: function(html) {
        hudson.loadInformations();
        $this.BASECAMP_DOM.html(html);

        if($this.BASECAMP_BUG_TIMER == null) {
          $this.BASECAMP_BUG_TIMER = setInterval(function() { $this.flashBugs() }, 1300);
        }
      }
    });
  };

  this.flashBugs = function() {
    $('.' + this.BASECAMP_BUG_CLASSNAME)
      .animate({opacity: 0.25}, 600, function() {
      $(this).animate({opacity: 1}, 600)
    });
  };
}

//$.fx.step.textShadowBlur = function(fx) {
//  $(fx.elem).css({textShadow: Math.floor(fx.now) + 'px ' + '0px ' + Math.floor(fx.now) + 'px red'});
//};