'use strict'; 
/**
 * 报价单 新增 
 * 作者：田小文
 * 时间：2018/05/20
 * 备注：
 **/

$(function() {
    //客户框回调行数
    var cbackCall = function(obj) {
        var than = $(obj).attr("data-than");
        $("#hid_than").val(parseFloat(than));
    }

    function selectSpec(num, obj) {
        var e = window.event || event;
        if (e.keyCode == 27)
            return;
        var cid = $("#cid").val();
        if (!cid) {
            layer.msg("请先选择客户");
            $(obj).val("");
            return;
        }
        if (num == 1) {
            publicSearch.textCall(obj, backCall);
        } else {
            publicSearch.btnCall(obj, backCall);
        }
    }
    //表格中的上下左右键切换input的焦点
    new tabTableInput("table", "text");

    var backCall = function(obj, ul) {
        //默认单位 渲染到对应的td中
        var tr = $(ul).parent().parent().parent(); //jq对象 
        var wgid = $(obj).attr("data-id");
        //判断该商品是否在本次入库单中存在 如果存在则不允许添加
        if (!checkGid(wgid)) {
            setRow(obj, ul, tr, wgid);
        } else {
            layer.alert('该商品已添加，不能继续添加.', function(index) {
                tr.find(".td_gname input[type='text']").val("");
                tr.find(".td_gname input[type='hidden']").val("");
                layer.close(index);
            });
        }
    }

    //移除本行
    function removeTr(obj) {
        var tr = $(obj).parent().parent();
        tr.remove();
        $(".td_xh").each(function(index, value) {
            $(value).html(index + 1);
        });
        //表格中的上下左右键切换input的焦点
        new tabTableInput("table", "text");
        calculateSum();
    }
    function setRow(obj, ul, tr, wgid) {
        saveState = 1; //设置保持状态为1
        tr.find(".a_del").removeClass("none");
        var div = $(ul).parent(); //td 下的div  
        var td = tr.find('.td_unit_id'); //jq对象
        resetTrInfo(div);
        var hidhtml = "<select name='nei_unit_id' onchange='specChange(this)'><option data-nei_num='1' value='" + $(obj).attr('data-unit-id') + "'>" + $(obj).attr('data-unit-name') + "</option></select><input type='hidden' name='unit_id' value='" + $(obj).attr('data-unit-id') + "' /><input type='hidden' class='jcprice' value='" + $(obj).attr("data-price") + "' />";
        td.html(hidhtml);
        tr.find(".td_cgname input").val($(obj).attr("data-name"));
        var than = $("#hid_than").val() || 1;
        tr.find(".td_than input").val(parseFloat(than)); //单价比
        getSpecInfo(wgid, td);
        //表格中的上下左右键切换input的焦点
        new tabTableInput("table", "text");
    }

    //规格选择
    function specChange(obj) {
        // var tr = $(obj).parent().parent();
        // var result = parseFloat(tr.find(".td_unit_id .jcprice").val())  * parseFloat($(obj).find("option:selected").attr("data-nei_num")).toFixed(4);
        // tr.find('.td_price input').val(result); 
    }

    //根据批发商商品编号获取规格信息
    function getSpecInfo(wgid, td) {
        var url = "/kuyou/Api/Public/getSpecInfo";
        $.ajax({
            type: "post",
            url: url,
            data: { "wgid": wgid },
            dataType: "json",
            success: function(data) {
                if (data.resultcode == 0) {
                    var str = "";
                    var result = data.result;
                    for (var i = 0; i < result.length; i++) {
                        str += "<option data-nei_num='" + result[i].num + "' value='" + result[i].unit_id1 + "'>" + result[i].uname + "</option>";
                    }
                    td.find("select").append(str);
                }
            },
            error: function() {
                layer.alert('获取规格时异常', { icon: 2 });
            }
        });
    }

    //判断该商品是否在本次入库单中存在 如果存在则不允许添加
    function checkGid(gid) {
        var wgids = document.getElementsByName("wgids");
        var k = 0;
        for (var i = 0; i < wgids.length; i++) {
            if (wgids[i].value == gid) {
                k++;
            }
            if (k > 1) {
                return true;
            }
        }
        return false;
    }

    $('.td_price input').change(function() {
        //判断是否填写数量 
        var tr = $(this).parent().parent();
        calculateTaxPrice(tr);
    });

    $('.td_tax_price input').change(function() {
        //判断是否填写数量 
        var tr = $(this).parent().parent();
        calculatePrice(tr);
    });

    //重置本行信息
    function resetTrInfo(div) {
        var tr = div.parent().parent(); //tr　　
        tr.find(".td_price input").val("");
        tr.find(".td_tax_price input").val("");
        tr.find(".td_than input").val("");
    }


    //计算税前金额
    function calculatePrice(tr) {
        var price = tr.find(".td_price input");
        var tax_price = tr.find(".td_tax_price input").val();
        var than = tr.find(".td_than input").val();
        if (IsNum(tax_price) && IsNum(than)) {
            var s = parseFloat((parseFloat(tax_price) / parseFloat(than)).toFixed(4));
            price.val(s);
        } else {
            price.val(0);
        }
    }

    //计算税后单价
    function calculateTaxPrice(tr) {
        var price = tr.find(".td_price input").val();
        var tax_price = tr.find(".td_tax_price input");
        var than = tr.find(".td_than input").val();
        if (IsNum(price) && IsNum(than)) {
            var s = parseFloat((parseFloat(price) * parseFloat(than)).toFixed(4));
            tax_price.val(s);
        } else {
            tax_price.val(0);
        }
    }
});