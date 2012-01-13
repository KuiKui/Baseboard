(function($){
  $.fn.wauto = function(opts){

    var options = $.extend({valueOfAnEmInPx: 14}, opts);
    var base = this;
    var width = 0;

    base.each(function(){
      if(($(this).width()) > width){
        width = $(this).width();
      }
    });

    base.width((width / options.valueOfAnEmInPx) + 'em');
  };
})(jQuery);