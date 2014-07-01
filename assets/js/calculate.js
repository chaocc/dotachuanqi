$(function(){
    //var indexis = document.getElementById("gridData") ? true : false; //是否是首页
    var articleis = document.getElementById("articalCon") ? true : false; //是否是文章页
    var clientWidth = $("body").width();
    var dataArray = [],hlistArray = [];
    /*$("#menu, #phonemenu").toggle(function(){
        //$("#menulist").show();
    },function(){
        //$("#menulist").hide();
    });*/
    $("#menu, #phonemenu").click(function(){
    	$("#menulist").toggle();
    });

    function throttle(method, context ,args) {
        clearTimeout(method.tId);
        //创建定时器
        method.tId = setTimeout(function() {
            //call()确保method()函数能在指定的context环境中执行
            //如果没有给出第二个参数，那么就在全局作用域内执行该方法
            method.call(context,args);
        }, 100);
    }
    //resize 时更改css
    function changeCss(clientWidth){
    	if (!clientWidth) {
    		clientWidth = $("body").width();
    	}
        if(clientWidth >=1200){
            $("#changecss").attr("href","#");
            $("#menulist").show();
        } else if(clientWidth >=1024 && clientWidth < 1200){
            $("#changecss").attr("href","assets/style/max1200.css");
			$("#menulist").hide();
        } else if(clientWidth >=660 && clientWidth < 1024){
            $("#changecss").attr("href","assets/style/max1024.css");
			$("#menulist").hide();
        } else if(clientWidth<660){
            $("#changecss").attr("href","assets/style/max660.css");
			$("#menulist").hide();
        }
    }
    changeCss(clientWidth);

    // resize 事件
    function resizeBody(){
        clientWidth = $("body").width();
        changeCss(clientWidth);
        if(articleis && hList){
            viewHlist(clientWidth);
        }
    }

    $(window).resize(function(){
      throttle(resizeBody);
    });
	//图片划过
	$("body").append("<img src=\"\" class=\"hovnoImg\" data-imgHov=\"Img\"/>");	
	var flagshow = true;
	var hoverImg = function(e,t){
		e = e||window.event;
		var _$this = t,
			_img = $(".hovnoImg[data-imgHov='Img']"),
			_lft = _$this.offset().left+_$this.outerWidth()+10,
			_top = _$this.offset().top+22,
			_x = e.pageX>_lft?e.pageX:_lft,
			_y = e.pageY>_top?e.pageY:_top,
			_scrollTop = $(window).scrollTop(),
			_winHeight = $(window).height();
		//设置图片宽高
		if(_$this.attr("data-size")){
			_size = _$this.attr("data-size").split(",")
			_img.css({"width":_size[0]+"px","height":_size[1]+"px"});	
		}
		if(_y-_scrollTop>_winHeight/2){
			_y = _y-_img.height()	
		}
		_img.stop().fadeIn(100).attr({
			"src":_$this.attr("data-hoveImg")	
		}).css({
			"left":_x,
			"top":_y
		});
	};

	bind_mouseenter(".table .tdleft .rwname,.newlist span", "a");
	bind_mouseenter(".c_card li h3", "li");
	bind_mouseenter(".picbox .font12", ".picbox", 0);
	bind_mouseenter(".picbox .fontnormal", ".picbox", 1);
	bind_mouseenter(".yw span", ".yw");
	bind_mouseenter(".gray span", ".gray");
	bind_mouseenter("[data-hoveImg]");
	
	function bind_mouseenter(s, p, i) {
		$(s).bind("mouseenter",function(e){
			var dom;
			if(p) {
				if(i) {
					dom = $(this).parents(p).find("[data-hoveImg]").eq(i);
				}
				else {
					dom = $(this).parents(p).find("[data-hoveImg]");
				}
			}
			else {
				dom = $(this);
			}
			
			if(dom.size() > 0) {
				flagshow = true;
				window.setTimeout(function(){
					if(flagshow){
						hoverImg(e,dom);		
					}
				},200);
			}
		});
	}
	$("[data-hoveImg],.table .tdleft .rwname,.c_card li h3,.newlist span,.picbox .font12,.picbox .fontnormal,.yw span,.gray span").bind("mouseleave",function(){
		var _$this = $(this);
		flagshow = false;
		$(".hovnoImg[data-imgHov='Img']").hide().attr("src","");
	});

});