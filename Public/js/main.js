var saveState = 0; //0.不需要保持1.需要保持
//文档加载完成时触发
$(function(){
	setHeight();
	// 设置首页
	$(".font-page").click(function(){
		var _this =this;
		var saveState = $("#iframe_content")[0].contentWindow.saveState;
		if(saveState){
			layer.confirm('页面内容未保存，离开此页面，数据将丢失，确定离开?', function(index){
				$(_this).addClass("active").siblings().children(".nav-top-li").removeClass("active");
				// $(".content").addClass("content_bg").children().hide();
				$(".main").show().siblings().hide();
				var src=$(_this).attr("data-href");
		        $("#iframe_main").attr("src",src);
				layer.close(index);
			},function(index){
				//留在此页面
				layer.close(index);
			}); 
	    }else{
	    	$(this).addClass("active").siblings().children(".nav-top-li").removeClass("active");
			// $(".content").addClass("content_bg").children().hide();
			$(".main").show().siblings().hide();
			var src=$(this).attr("data-href");
	        $("#iframe_main").attr("src",src);
	    }
	})
	// 设置顶部导航和左边导航
	$(".nav-top-li").click(function(){
		var _this =this;
		$(".main").hide().siblings().show();
		$(this).addClass("active").siblings().removeClass("active");
		$(this).addClass("active").parent().siblings("div").removeClass("active");
		$(".nav-left ul").eq($(this).index()).show().siblings().hide();
	});
	$(".nav-left-li").click(function(){
		//判断页面的内容是否保存
		var _this =this;
		var saveState = $("#iframe_content")[0].contentWindow.saveState;
		if(saveState){
			layer.confirm('页面内容未保存，离开此页面，数据将丢失，确定离开?', function(index){
				//离开页面
			  	$(_this).addClass("active").siblings().removeClass("active");
				$(_this).parent().siblings().children().removeClass("active");
				var src = $(_this).attr('data-href'); 
				$("#iframe_content").attr('src',src); 
			    layer.close(index);
			},function(index){
				//留在此页面
				layer.close(index);
			});
		}else{
			$(this).addClass("active").siblings().removeClass("active");
			$(this).parent().siblings().children().removeClass("active");
			var src = $(this).attr('data-href'); 
			$("#iframe_content").attr('src',src);
		}
		
	});
	
    // 账户信息
    $(".account").click(function(){
		//判断页面的内容是否保存
		var _this =this;
		var saveState = $("#iframe_content")[0].contentWindow.saveState;
		if(saveState){
	        layer.confirm('页面内容未保存，离开此页面，数据将丢失，确定离开?', function(index){
				//离开页面 
		    	$(".main").show().siblings().hide();
		        var src=$(_this).attr("data-href");
		        $("#iframe_main").attr("src",src);
			    layer.close(index);
			},function(index){
				//留在此页面
				layer.close(index);
			});
	    }else{
	    	$(".main").show().siblings().hide();
	        var src=$(this).attr("data-href");
	        $("#iframe_main").attr("src",src);
	    } 
    });

	window.onresize=function(){
		setHeight();
	}

	function setHeight(){
		$("#iframe_main").height($(window).height()-100);
		$("#nav-left").height($(window).height()-100);
		$("#iframe_content").height($(window).height()-100);
		
	} 
}); 

//键盘f5 ctrl+r 按下事件
document.onkeydown=function(event){
    var e = event || window.event || arguments.callee.caller.arguments[0];
    if(e && e.keyCode==116){ // 按 F5  
        e.preventDefault();
        var saveState1 = $("#iframe_content")[0].contentWindow.saveState;
        if(saveState1){
            layer.confirm('页面内容未保存，离开此页面，数据将丢失，确定离开?', function(index){ 
            	$("#iframe_content")[0].contentWindow.saveState =0;
                location.reload(); 
                layer.close(index);
            },function(index){ 
                //留在此页面
                layer.close(index);
            });
        }else{ 
            $("#iframe_content")[0].contentWindow.location.reload(); 
        }
        // alert("按F5");
    }    
}


	// function setIframeSrc(href){ 
	// 	$("#iframe_content").attr('src',href);
	// }