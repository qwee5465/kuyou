// 文档加载完成
$(function(){
	$("tbody tr").click(function(){
		$(this).addClass("success").siblings().removeClass("success");
	})
	
	
})
// // 滚动条事件-导航栏
// window.onscroll=function(){
// 	var t =document.documentElement.scrollTop||document.body.scrollTop;
// 	var nav=document.getElementById("nav");
// 	if(t>0){
// 		nav.classList.add("s-nav");
// 	}else{
// 		nav.classList.remove("s-nav");
// 	}
// }