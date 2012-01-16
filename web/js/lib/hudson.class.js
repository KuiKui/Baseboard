function Hudson()
{
  /**
   * Paramètre utile au fonctionnement de la classe.
   */
  this.HUDSON_URL = 'loadHudsonFails.php';
  this.HUDSON_CLASSNAME = 'hasFailedJobs';
  this.HUDSON_TIMER = null;
  this.HUDSON_FLASH_TIMER = null;
  this.OPTIONS = {
    timeToTick: 3 * 60 * 1000,
    flashColor: 'rgb(240, 189, 102)'
  };
  
  this.init = function() {
    var $this = this;

    $this.loadInformations();

    $this.HUDSON_TIMER = setInterval(function() {$this.loadInformations()}, this.OPTIONS.timeToTick);
  };

  this.loadInformations = function() {
    var $this = this;

    $.ajax({
      url: $this.HUDSON_URL,
      success: function(json){
        $this.checkErrors($.parseJSON(json));
      }
    });
  };

  this.checkErrors = function(json) {
    var $this = this;
    
    $.each(json, function(index, value) {
      var project = $('#' + index);
      
      if(project != undefined) {
        if(value == 0) {
          project.removeClass($this.HUDSON_CLASSNAME);
        } else {
          project.addClass($this.HUDSON_CLASSNAME);
        }
      }
    });

    if($this.HUDSON_FLASH_TIMER == null) {
      $this.HUDSON_FLASH_TIMER = setInterval(function() { $this.flashErrors() }, 3000);
    }
  };

  this.flashErrors = function() {
    var $this = this;

    $('.' + this.HUDSON_CLASSNAME)
      .animate({backgroundColor: $this.OPTIONS.flashColor}, 1000, function() {
      $(this).animate({backgroundColor: '#101010'}, 1000);
    });
  };
}