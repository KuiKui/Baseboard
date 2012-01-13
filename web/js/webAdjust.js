(function($){
  $.webAdjust = function(el, options){
    var base = this;

    base.$el = $(el);
    base.el = el;

    base.$el.data("webAdjust", base);

    base.init = function(){
      base.options = $.extend(base.defaultOptions,$.webAdjust.defaultOptions, options);

      base.adjust();

      base.$el.resize(function(){
        base.adjust(base.options.wrapper);
      });
    };

    base.pxToInt = function(value){
      return value.substr(0, value.length -2);
    };

    base.adjust = function(){
      var ratio = base.$el.height() / base.options.wrapper.height();
      var newFontSize = base.pxToInt($('body').css('font-size')) * ratio * 0.95;
      if(newFontSize > base.options.maxFontSize){
        newFontSize = base.options.maxFontSize;
      }
      
      $('body').css({'font-size': newFontSize});
    };

    base.init();
  };

  $.webAdjust.defaultOptions = {
    wrapper: $(window),
    maxFontSize: 27
  };

  $.fn.webAdjust = function(options){

    return this.each(function(){
      (new $.webAdjust(this, options));
    });
  };

})(jQuery);