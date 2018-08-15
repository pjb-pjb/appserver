var exec = require('child_process').exec; 
setInterval(function(){
exec("git pull",function(err,data){console.log(1)});
},2000);
