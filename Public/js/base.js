'use strict'; //严谨模式
/**
 * 底层方法
 * 作者：田小文
 * 时间：2018/05/12
 * 备注：
 **/

/**
 * 格式化日期格式
 */
Date.prototype.format = function(format) {
    var date = {
        "M+": this.getMonth() + 1,
        "d+": this.getDate(),
        "h+": this.getHours(),
        "m+": this.getMinutes(),
        "s+": this.getSeconds(),
        "q+": Math.floor((this.getMonth() + 3) / 3),
        "S+": this.getMilliseconds()
    };
    if (/(y+)/i.test(format)) {
        format = format.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length));
    }
    for (var k in date) {
        if (new RegExp("(" + k + ")").test(format)) {
            format = format.replace(RegExp.$1, RegExp.$1.length == 1 ?
                date[k] : ("00" + date[k]).substr(("" + date[k]).length));
        }
    }
    return format;
}

/**
 * base对象
 */
// var base = {
//     isReuqestUnit:true, //是否需要请求
//     uList:localStorage.uList,
//     /**
//      * 获取单位数据
//      */
//     getUnitAllInfo: function() {

//     }
// }