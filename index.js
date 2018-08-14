/**
 * Created by doctor on 15-8-8.
 */

const fs = require('fs');
var exec = require('child_process').exec;

var fileName = {
    Customer: ["AccountController.php", "AddrController.php", "AddressController.php", "CarController.php", "ContactController.php", "EditaccountController.php", "FacebookController.php", "ForgotController.php",],
    Catalogsearch: [],
    Checkout: [],
    Catalog: [],
    General: [],
    Payment: [],
    Store: []
};
for (var val in fileName) {
    fileName[val].forEach(function (val1,index1) {
        fs.watch(`./${val}/controllers/${val1}`,( function () {
            var count = 0;
            if(count%2 == 0){
                var p = new Promise(function (resolve,reject) {
                    exec("git add *",function(err,data){
                        resolve();
                    });
                }).then(function () {
                    exec('git commit -m "123"',function(err,data){
                        
                    });
                }).then(function () {
                    exec("git pull",function(err,data){
                        
                    });
                }).then(function () {
                    exec("git push",function(err,data){
                    });
                })
                return function(){
                    count++;
                    console.log("文件" + fileName + " 内容刚刚改变。。第" + count + "次" );
                };
            }
        })());
    });
};

console.log("watching file...");
