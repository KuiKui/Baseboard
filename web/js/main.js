$(document).ready(function(){
   $.PeriodicalUpdater({
      url : 'load.php',
      minTimeout: 60000,
      multiplier: 1
   },
   function(data){
      $('#dynamicContent').append(data);
   });
})
