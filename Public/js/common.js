'use strict'; //严谨模式
/**
 * 存放公共组件、私有组件和通用方法
 * 作者：田小文
 * 时间：2016/8/17
 * 维护：田小文
 * 备注：
 **/

var saveState = 0; //0.不需要保持1.需要保持
(function() {
    // 表格点击状态
    $("table tbody tr").click(function() {
        $(this).addClass("active").siblings().removeClass("active");
    });
    // 输入框和select框点击状态
    $("input[type!=submit]").click(function() {
        $(this).addClass("active");
        $(this).siblings(".search-style").addClass("active");
    });
    $("input[type!=submit]").blur(function() {
        $(this).removeClass("active");
        $(this).siblings(".search-style").removeClass("active");
    });
    $("select").click(function() {
        $(this).addClass("active");
    });
    $("select").blur(function() {
        $(this).removeClass("active");
    })
    $('.prev img').hover(function() {
        $(this).attr('src', '/kuyou/Public/img/prev_active.png');
    }, function() {
        $(this).attr('src', '/kuyou/Public/img/prev.png');
    });
    $('.next img').hover(function() {
        $(this).attr('src', '/kuyou/Public/img/next_active.png');
    }, function() {
        $(this).attr('src', '/kuyou/Public/img/next.png');
    });
})();


// Number.prototype.toFixed = function(s) {
//     var that = this,changenum,index;
//     // 负数
//     if(this < 0){
//         that = -that;
//     }
//     changenum = (parseInt(that * Math.pow(10, s) + 0.5) / Math.pow(10, s)).toString();
//     index = changenum.indexOf(".");
//     if (index < 0 && s > 0) {
//         changenum = changenum + ".";
//         for (var i = 0; i < s; i++) {
//             changenum = changenum + "0";
//         }


//     } else {
//         index = changenum.length - index;
//         for (var i = 0; i < (s - index) + 1; i++) {
//             changenum = changenum + "0";
//         }


//     }
//     if(this<0){
//         return -changenum; 
//     }else{
//         return changenum; 
//     }     
// }

//判断是否为数字
function IsNum(s) {
    if (s != null && s != "") {
        return !isNaN(s);
    }
    return false;
}
/**
 * 验证不是为手机号码
 */
function IsPhone(phone) {
    var myreg = /^(((13[0-9]{1})|(14[0-9]{1})|(17[0]{1})|(15[0-3]{1})|(15[5-9]{1})|(18[0-9]{1}))+\d{8})$/;
    return myreg.test(phone);
}

/*
 *监听商品文本框失去内容发生变化时如果是无效数据自动清空并提示无该商品
 */
// var sreach_input = $(".select-sreach-input").length;
// if(sreach_input>0){
//     $(".select-sreach-input").change(function(){
//         var wgids_input = $(this).parent().find("input[name='wgids']").val();
//         if(wgids_input==""){ 
//             $(this).val("");
//         }
//     });
// }



/**
 * 通用搜索对象 单位 客户 供应商等 使用
 */
var apiUrl = "/kuyou/Api/Public/";

var system_param = "";

function calculatePremark(tr) {
    //新加单位备注  数量+单位 
    var num = tr.find(".td_num input").val();
    var uname = tr.find(".td_unit_id select option:selected").text();
    var str = "";
    //获取单位转换规则 
    if (system_param) {
        switch (system_param.param_is_convert_unit) {
            case "0":
                str = num + uname;
                break;
            case "1":
                if (uname == "公斤") {
                    str = num * 2 + "斤";
                } else {
                    str = num + uname;
                }
                break;
            case "2":
                if (uname == "斤") {
                    str = num / 2 + "公斤";
                } else {
                    str = num + uname;
                }
                break;
        }
        tr.find(".td_p_remark input").val(str);
    } else {
        var url = apiUrl + "getSystemParam";
        $.post(url, {}, function(data) {
            if (data != 0) {
                system_param = data;
                calculatePremark(tr);
            } else {
                console.log("获取系统参数错误");
            }
        }, "json");
    }
}

var publicSearch = {
    /**
     * 按钮调用
     * @param  {[object]} _this [文本框对象]
     * @param  {Function} fn   [回调函数,有则调用无则不调用] 
     */
    textCall: function(_this, fn, cid) {
        //判断上一行是否已经选择商品 
        var t_tr = $(_this).parent().parent().parent();
        var row_index = t_tr.index();
        var event = event || window.event;
        if (event.keyCode == 40 || event.keyCode == 38 || event.keyCode == 37 || event.keyCode == 13)
            return;
        if (row_index > 0) {
            var wgid_val = t_tr.parent().find("tr").eq(row_index - 1).find("input[name='wgids']").val();
            if (wgid_val == "") {
                _this.value = "";
                // layer.msg("请先填写上一行.");
                return;
            }
        }
        var value = $(_this).val();
        if (value) { //输入框非空  
            var hidden = this.getHidden(_this);
            var input = this.getText(_this);
            var ul = this.getUl(_this);
            var name = $(input).attr('data-name');
            var page = $(input).attr('data-page');
            var id = $(input).attr('data-id');
            var url = apiUrl + page;
            var data = {};
            data[name] = value;
            if (page == "getGoodsInfoOut")
                data['c_id'] = cid;
            $.post(url, data, function(data) {
                if (data.resultcode == 0) {
                    var str = publicSearch.dataResultCall(0, data, id, name, "", page);
                    ul.html(str); //用生产的str 字符串替换ul中的文本
                    ul.show(); //显示ul 
                    ul.find('li').click(function(e) { //ul中的li点击事件
                        e.stopPropagation();
                        publicSearch.liCall(input, this, hidden, page);
                        if (page == "getGoodsInfoOut"){
                            publicSearch.getOutPrice(this,this.dataset.id,cid,ul,fn);
                        }else if(page == "getGoodsInfoByText"){
                            publicSearch.getJoinPrice(this,this.dataset.id,ul,fn);
                        }else{
                            if (fn) //判断是否传入回调行数
                                fn(this, ul); //执行回调行数
                        }
                    });
                    $("body").click(function() { //添加监听事件 ，body被点击时隐藏ul
                        ul.hide();
                    });
                }
            }, "json");
        }
    },

    /**
     * 按钮调用
     * @param  {[object]} _this [按钮对象]
     * @param  {Function} fn   [回调函数,有则调用无则不调用] 
     */
    btnCall: function(_this, fn, cid) {
        var ul = this.getUl(_this);
        if (ul.is(":hidden")) {
            var hidden = this.getHidden(_this);
            var input = this.getText(_this);
            var page = input.attr('data-page');
            var name = input.attr('data-name');
            var liClass = input.attr('data-li-class');
            var id = input.attr('data-id');
            var data_layer = input.attr('data-layer');
            var url = apiUrl + page;
            var data = { btn: 1 };
            if (page == "getGoodsInfoOut" || page == "getGoodsInfoByText") {
                data['c_id'] = cid;
                layer.open({
                    type: 2,
                    closeBtn: 1,
                    area: ['900px', '500px'],
                    title: false,
                    content: apiUrl + 'goodsSreach.html'
                });
            } else {
                $.post(url, data, function(data) {
                    if (data.resultcode == 0) {
                        var str = publicSearch.dataResultCall(1, data, id, name, liClass, page);
                        if (data_layer == "1") {
                            if (layer) {
                                var str_content = "<ul class='select-sreach-list-open' id='ul_layer'>" + str + "</ul>";
                                //页面层-自定义
                                var layer_index = layer.open({
                                    type: 1,
                                    title: false,
                                    area: ['900px', '500px'],
                                    closeBtn: 0,
                                    shadeClose: true,
                                    content: "" + str_content + ""
                                });
                                $("body").addClass("body-ohid");
                                $("#ul_layer li").click(function() {
                                    publicSearch.liCall(input, this, hidden, page);
                                    $("body").removeClass("body-ohid");
                                    if (fn) //判断是否传入回调行数
                                        fn(this, ul); //执行回调行数
                                    layer.close(layer_index);
                                });
                            }
                        } else {
                            ul.html(str); //用生产的str 字符串替换ul中的文本
                            ul.show(); //显示ul
                            ul.find('li').click(function(e) { //ul中的li点击事件
                                e.stopPropagation();
                                publicSearch.liCall(input, this, hidden, page);
                                if (fn) //判断是否传入回调行数
                                    fn(this, ul); //执行回调行数
                            });
                            $("body").click(function() { //添加监听事件 ，body被点击时隐藏ul
                                ul.hide();
                            });
                        }
                    } else if (data.resultcode == 1) {
                        layer.msg("无数据,请前往添加");
                    }
                }, "json");
            }
        } else {
            ul.hide();
        }
    },
    //获取隐藏域对象
    getHidden: function(obj) {
        return $(obj).parent().find("input[type='hidden']");
    },
    //获取文本框对象
    getText: function(obj) {
        // return $(obj).parent().find(".select-sreach-input");
        return $(obj).parent().find("input[type='text']");
    },
    getUl: function(obj) {
        return $(obj).parent().find("ul");
    },
    /**
     * 
     * @param  {[object]} input [文本框对象]
     * @param  {[object]} obj   [li对象]
     * @param  {[object]}       [隐藏域对象] 
     */
    liCall: function(input, obj, hidden, page) {
        var id = $(obj).attr('data-id');
        if (id == "") {
            $(input).val('');
            hidden.val('');
        } else {
            if (page == "getClient") {
                $("#hid_than").val(obj.dataset.than);
                console.log($("#hid_than").val());
            }
            if (page == "getGoodsInfoByText" || page == "getGoodsInfoOut") {
                $(input).val($(obj).attr("data-name"));
            } else {
                $(input).val($(obj).html()); //用li的文本内容替换文本框的值
            }
            hidden.val(id); //用li中的data-id属性值替换隐藏域的值 
        }
        $(obj).parent().hide(); //隐藏ul
    },

    /**
     * 获取商品入库记忆价
     */
    getJoinPrice:function(that,wgid,ul,fn){
        var url = apiUrl + 'getJoinPrice';
        var sid = $("#sid").val();
        $.ajax({
            url:url,
            data: {wgid:wgid,sid:sid},
            type:"POST",
            dataType:'json',
            success:function(res){
                if(res.resultcode==0){
                    that.dataset.price=res.result.price;
                }else{
                    that.dataset.price='';
                }
                if (fn) //判断是否传入回调行数
                    fn(that, ul); //执行回调行数
            },
            error:function(e){
                console.log("出错了");
            }
        })
    },

    /**
     * 获取商品出库记忆价 
     */
    getOutPrice: function(that,wgid,cid,ul,fn) {
        var url = apiUrl + 'getOutPrice';
        $.ajax({
            url:url,
            data: {wgid:wgid,c_id:cid},
            type:"POST",
            dataType:'json',
            success:function(res){
                if(res.resultcode==0){
                    that.dataset.price=res.result.price;
                }else{
                    that.dataset.price='';
                }
                if (fn) //判断是否传入回调行数
                    fn(that, ul); //执行回调行数
            },
            error:function(e){
                console.log("出错了");
            }
        })
    },

    /**
     * 请求数据成功后调用
     * @param  {[json]}   data [返回的json数据]
     * @return {string} [json数据生成的字符串]
     */
    dataResultCall: function(num, data, id, name, liClass, page) {
        var str = "";
        var result = data.result;
        for (var i = 0; i < result.length; i++) {
            if (i == 0)
                str += "<li class='" + liClass + " active' title='" + result[i][name] + "' data-id='" + result[i][id] + "'";
            else
                str += "<li class='" + liClass + "' title='" + result[i][name] + "' data-id='" + result[i][id] + "'";
            if (result[i]['unit_id']) {
                str += " data-unit-id='" + result[i]['unit_id'] + "'";
                str += " data-unit-name='" + result[i]['unit_name'] + "'";
            }
            if (result[i]['price']) {
                str += " data-price='" + result[i]['price'] + "'";
            }
            if (result[i]['than']) {
                str += " data-than='" + result[i]['than'] + "'";
            }else{
                str += " data-than='" + $("#hid_than").val() + "'";
            }
            if (page == "getGoodsInfoByText" || page == "getGoodsInfoOut") {
                str += " data-brand='" + result[i]['brand_name'] + "'";
                str += " data-name='" + result[i][name] + "'";
                str += ">" + result[i]['title'] + "</li>";
            } else {
                str += ">" + result[i][name] + "</li>";
            }
        }
        return str;
    }
};

function getClientContractInfo(cid) {
    // var url = apiUrl + "getClientContractInfo";
    // var data ={cid:cid};
    // $.post(url, data, function(data) { 
    //     if(data.resultcode==10)
    //         layer.msg(data.msg);
    //     else
    //         console.log("启用合同");
    // }, "json");
}


$(".select-sreach-input").keydown(function(e) {
    var ul = $(this).parent().find("ul");
    if (!ul.is(':hidden')) {
        if (e && e.keyCode == 40) { //下箭头  
            var index = 0;
            ul.find("li").each(function(ii, value) {
                if (value.classList == "active") {
                    index = ii;
                    return false;
                }
            });
            ul.find("li").eq(index + 1).addClass("active").siblings().removeClass("active");
        } else if (e && e.keyCode == 38) { //上箭头   
            var index = 0;
            ul.find("li").each(function(ii, value) {
                if (value.classList == "active") {
                    index = ii;
                    return false;
                }
            });
            ul.find("li").eq(index - 1).addClass("active").siblings().removeClass("active");
        } else if (e && e.keyCode == 13) {
            $(ul.find("li.active")).trigger("click");
        } else if (e && e.keyCode == 27) {
            ul.hide();
        }
    }
});
//验证数字文本框
$(".t-number").keypress(function(e) {
    var e = e || event;
    var val = $(this).val();
    if (e.keyCode == 46) {
        if (val.indexOf('.') >= 0) {
            return false;
        }
    }
    //console.log(e.keyCode);
    return (/[\d.]/.test(String.fromCharCode(event.keyCode)));
});

//监听键盘事件   
var layui_btn = 0;
$("body").keydown(function(e) {
    if (e.keyCode == 13) { // enter键  
        var btn0 = $(".layui-layer-btn0");
        if (btn0.length > 0 && layui_btn == 1) {
            $(".layui-layer-btn0").trigger("click");
            layui_btn = 0;
        } else if (btn0.length > 0) {
            layui_btn++;
        }
    } else if (e.keyCode == 27) { //esc键
        var btn1 = $(".layui-layer-btn1");
        if (btn1.length > 0 && layui_btn == 1) {
            $(".layui-layer-btn1").trigger("click");
            layui_btn = 0;
        } else if (btn0.length > 0) {
            layui_btn++;
        }
    }
});

//根据批发商商品编号获取规格信息
function getSpecInfo(wgid) {
    var url = "/kuyou/Api/Public/getSpecInfo";
    $.ajax({
        type: "post",
        url: url,
        data: data,
        dataType: "json",
        success: function(data) {
            if (data.resultcode == 0) {

            } else {

            }
        },
        error: function() {
            layer.alert('不能重复保存。', { icon: 2 });
        }
    });
}


//键盘f5 ctrl+r 按下事件 焦点在哪个页面 就要在哪个页面写监听事件
document.onkeydown = function(event) {
    var e = event || window.event || arguments.callee.caller.arguments[0];
    if (e && e.keyCode == 116) { // 按 F5  
        e.preventDefault();
        var saveState1 = saveState;
        if (saveState1) {
            layer.confirm('页面内容未保存，离开此页面，数据将丢失，确定离开?', function(index) {
                saveState = 0;
                location.reload();
                layer.close(index);
            }, function(index) {
                //留在此页面
                layer.close(index);
            });
        } else {
            location.reload();
        }
    } else if (e.keyCode == 8) { //退格键 返回上一页  
        if (document.activeElement.tagName == "BODY") {
            e.preventDefault();
            var saveState1 = saveState;
            if (saveState1) {
                layer.confirm('页面内容未保存，离开此页面，数据将丢失，确定离开?', function(index) {
                    saveState = 0;
                    history.back(); //返回上一页
                    layer.close(index);
                }, function(index) {
                    //留在此页面
                    layer.close(index); //停留在本页面
                });
            } else {
                history.back(); //返回上一页
            }
        }
    }
}

var tabTableInput = function(tableId, inputType) {
    var rowInputs = [];
    var trs = $("#" + tableId).find("tr");
    var inputRowIndex = 0;
    $.each(trs, function(i, obj) {
        if ($(obj).find("th").length > 0) { //跳过表头
            return true;
        }
        var rowArray = [];
        var thisRowInputs;
        if (!inputType) { //所有的input
            thisRowInputs = $(obj).find("input:not(:disabled):not(:hidden):not([readonly]),select:not(:disabled)");
        } else {
            thisRowInputs = $(obj).find("input:not(:disabled):not(:hidden):not([readonly])[type=" + inputType + "],select:not(:disabled)");
        }
        if (thisRowInputs.length == 0)
            return true;
        thisRowInputs.focus(function() {
            $(this).parents("tr").addClass("active").siblings().removeClass("active");
        });

        thisRowInputs.each(function(j) {
            $(this).attr("_r_", inputRowIndex).attr("_c_", j);
            rowArray.push({
                "c": j,
                "input": this
            });

            $(this).keydown(function(evt) {
                var r = $(this).attr("_r_");
                var c = $(this).attr("_c_");
                var ul = $(this).parent().find("ul");
                var tRow;
                if (evt.which == 38) { //上
                    if (this.nodeName != "SELECT") {
                        //ul存在的话
                        if (ul.length > 0) {
                            if (ul.is(':hidden')) {
                                if (r == 0)
                                    return;

                                r--; //向上一行

                                tRow = rowInputs[r];
                                if (c > tRow.length - 1) {
                                    c = tRow.length - 1;
                                }
                            }
                        } else {
                            if (r == 0)
                                return;

                            r--; //向上一行

                            tRow = rowInputs[r];
                            if (c > tRow.length - 1) {
                                c = tRow.length - 1;
                            }
                        }
                        $(rowInputs[r].data[c].input).focus();
                        setTimeout(function() {
                            $(rowInputs[r].data[c].input).select();
                        }, 10);
                    }
                } else if (evt.which == 40) { //下
                    if (this.nodeName != "SELECT") {
                        if (ul.length > 0) {
                            if (ul.is(':hidden')) {
                                if (r == rowInputs.length - 1) { //已经是最后一行
                                    return;
                                }

                                r++;
                                tRow = rowInputs[r];
                                if (c > tRow.length - 1) {
                                    c = tRow.length - 1;
                                }
                            }
                        } else {
                            if (r == rowInputs.length - 1) { //已经是最后一行
                                return;
                            }

                            r++;
                            tRow = rowInputs[r];
                            if (c > tRow.length - 1) {
                                c = tRow.length - 1;
                            }
                        }
                        $(rowInputs[r].data[c].input).focus();
                        setTimeout(function() {
                            $(rowInputs[r].data[c].input).select();
                        }, 10);
                    }

                } else if (evt.which == 37) { //左  
                    if (this.nodeName == "SELECT") {
                        evt.preventDefault();
                    }
                    if (r == 0 && c == 0) { //第一行第一个,则不执行操作
                        return;
                    }
                    if (c == 0) { //某行的第一个,则要跳到上一行的最后一个,此处保证了r大于0
                        r--;
                        tRow = rowInputs[r];
                        c = tRow.length - 1;
                    } else { //否则只需向左走一个
                        c--;
                    }
                    if (ul.length <= 0) {
                        $(this).parent().parent().find("ul").hide();
                    } else {
                        ul.hide();
                    }
                    $(rowInputs[r].data[c].input).focus();
                    setTimeout(function() {
                        $(rowInputs[r].data[c].input).select();
                    }, 10);
                } else if (evt.which == 39) { //右 
                    if (this.nodeName == "SELECT") {
                        evt.preventDefault();
                    }
                    tRow = rowInputs[r];
                    if (r == rowInputs.length - 1 && c == tRow.length - 1) { //最后一个不执行操作
                        return;
                    }

                    if (c == tRow.length - 1) { //当前行的最后一个,跳入下一行的第一个
                        r++;
                        c = 0;
                    } else {
                        c++;
                    }
                    if (ul.length <= 0) {
                        $(this).parent().parent().find("ul").hide();
                    } else {
                        ul.hide();
                    }
                    $(rowInputs[r].data[c].input).focus();
                    setTimeout(function() {
                        $(rowInputs[r].data[c].input).select();
                    }, 10);
                } else if (evt.which == 9) {
                    if (ul.length <= 0) {
                        $(this).parent().parent().find("ul").hide();
                    } else {
                        ul.hide();
                    }
                }
            });
        });

        rowInputs.push({
            "length": thisRowInputs.length,
            "rowindex": inputRowIndex,
            "data": rowArray
        });

        inputRowIndex++;

    });
}

//返回浏览器历史页面
function backPage() {
    var saveState1 = saveState;
    if (saveState1) {
        layer.confirm('页面内容未保存，离开此页面，数据将丢失，确定离开?', function(index) {
            saveState = 0;
            history.go(-1);
        }, function(index) {
            //留在此页面
            layer.close(index);
        });
    } else {
        history.go(-1);
    }
}