$(function(){
	var obj_current = null; // 当前的 selectbg 对象
	var obj_addoption = $('.option1');
	var maxoption = 5;
	
	// option2 option3 绑定
	function bind_option2(val) {
		var opt1_data = $.trim(obj_current.find('.option1 > p.selected').attr('data-filter')).toLowerCase();
		if (opt1_data == -1) {
			return false;
		}
		
		var obj_opt2 = obj_current.find('div.option2');
		var obj_opt2_selected = obj_opt2.find('p.selected');
		var obj_opt2_ps = obj_opt2.find('div.ps');
		var obj_opt3 = obj_current.find('div.option3');
		var obj_dele = obj_current.find('.dlt');
		
		// 给 ps 进行事件绑定
		if ( ! obj_opt2_selected.attr('ebind')) {
			obj_opt2_selected.click(function() {
				$(this).parent().parent().siblings().find('.option2 > .ps').hide();
				obj_opt2_ps.toggle();
			});
			obj_opt2_selected.attr('ebind', '1');
		}
		//删除
		obj_dele.css("display","block");
		obj_dele.bind("click",function(){
			$(this).parent().remove();
			$('.option1:last').parent().show();
			return false;
		});
		// 设置 option2 和 option3
		var process_flag = true;
		var num_option = false;
		var data_filter = '';
		switch(opt1_data)
		{
			case 'cost':
			case 'atk':
			case 'life':
				obj_opt2_ps.html($('#selectCompareSymbol').html());
				
				obj_opt2.show();
				obj_opt3.show();
				if(val){
					var temp = val.split(':');
					data_filter = temp[0];
					obj_opt3.find('.input').val(temp[1]);
				}
				num_option = true;
				break;
			case 'cardset':
				obj_opt2_ps.html($('#selectCardSet').html());
				break;
			case 'cardrace':
				obj_opt2_ps.html($('#selectCardRace').html());
				break;
			case 'cardtype':
				obj_opt2_ps.html($('#selectCardType').html());
				break;
			case 'class':
				obj_opt2_ps.html($('#selectCardClass').html());
				break;
			default:
				process_flag = false; // 如果没有命中，后续则终止
				break;
		}
		// 设置完成
		if (! process_flag) {
			return false;
		}
		else{
			if(num_option){
				obj_opt2.css('width', '60px');
			}
			else{
				obj_opt2.css('width', '80px');
				obj_opt2.show();
				obj_opt3.hide();
				if(val){
					data_filter = val;
				}
			}
		}
		
		if(val && data_filter && obj_opt2_ps.find('p[data-filter='+data_filter+']').size()>0){
			obj_opt2_selected.find('span').text(obj_opt2_ps.find('p[data-filter='+data_filter+']').find('span').text());
			obj_opt2_selected.attr('data-filter', data_filter);
		}
		else{
			obj_opt2_selected.find('span').text(obj_opt2_ps.find('p:first').find('span').text());
			obj_opt2_selected.attr('data-filter', obj_opt2_ps.find('p:first').attr('data-filter'));
		}
		
		obj_opt2_ps.find("p:not(.strong)").click(function (){
			var data_filter = $.trim($(this).attr('data-filter')).toLowerCase();
			obj_opt2_selected.attr('data-filter', data_filter);
			
			obj_opt2_selected.find('span').text($(this).find('span').text());
			obj_opt2_ps.hide();
		});
	}
	
	// option1 事件绑定
	function bind_option1 (obj) {
		var objs = obj ? $('.option1:last') : $('.option1') ;
		objs.each(function(){
			var obj_parent = $(this).parent();
			var obj_self = $(this);
			var but_click = $(this).find("p.selected");
			var domShow = $(this).find(".ps");
			var changeTxt = $(this).find(".selected span");
			but_click.click(function() {
				$('div.valid > div.option2 > .ps').hide();
				$(this).parent().parent().siblings().find('.option1 > .ps').hide();
				domShow.toggle();
			});
			domShow.find("p:not(.strong)").click(function (){
				domShow.hide();
				
				// 检查  but_click 的 data-filter 的值，如果为 -1 的话，在后面增加一个 clone 元素
				var p_data_filter = $.trim(but_click.attr('data-filter')).toLowerCase();
				if (p_data_filter == -1)
				{
					obj_addoption = obj_parent.clone().insertAfter(obj_parent);
					bind_option1(true);
				}
				
				obj_self.css('width', '86px').css('z-index', 'auto');
				obj_parent.addClass('valid'); // 标记选择组有效
				if($('div.valid').size() >= maxoption){
					obj_addoption.hide();
				}
				
				var data_filter = $.trim($(this).attr('data-filter')).toLowerCase();
				but_click.attr('data-filter', data_filter);
				

				obj_current = obj_parent;
				bind_option2();
				
				changeTxt.text($(this).text());
			});
		});
	}
	
	bind_option1();
	
	$('#searchForm').submit(function(){
		var filter = '';
		$('div.valid').each(function(){
			var query_key = $.trim($(this).find('div.option1 > p.selected').attr('data-filter'));
			if (query_key != '-1') {
				filter += (filter ? '&' : '') + query_key;
				filter += '=' + $.trim($(this).find('div.option2 > p.selected').attr('data-filter'));
				if ( $(this).find('div.option3:visible').size() > 0) {
					filter += ':' + $.trim($(this).find('div.option3:visible > input').val());
				}
			}
		});
		// 稀有度
		if ($('div.s2_1 > ul li span.checked').size() > 0) {
			filter += (filter ? '&' : '') + 'cardrarity=';
			$('div.s2_1 > ul li span.checked').each(function(){
				filter += $.trim($(this).attr('data-filter')) + ':';
			});
			filter = filter.replace(/:$/ig, '');
		}
		// 卡牌效果
		if ($('div.s2_2 > ul li span.checked').size() > 0) {
			filter += (filter ? '&' : '') + 'cardpower=';
			$('div.s2_2 > ul li span.checked').each(function(){
				filter += $.trim($(this).attr('data-filter')) + ':';
			});
			filter = filter.replace(/:$/ig, '');
		}
		//filter = filter ? encodeURIComponent(filter) : '';
		$('#searchFilter').val(filter);
		return true;
	});
	
	$('#searchClear').click(function(){
		$('#searchFilter').val('');
		$('div.valid').remove();
		obj_addoption.show();
		$('div.s2_1 > ul li span').attr('class','checkbox');
		$('div.s2_2 > ul li span').attr('class','checkbox');
		$("input[name='searchkw']").val('');
	});
	
	$(".checkbox").click(function (){
		$(this).toggleClass('checked').toggleClass('checkbox');
	});
	
	function dobind_option1(data_filter,val){
		if($('div.valid').size() >= maxoption){
			return;
		}
		var objs = $('.option1:last');
		objs.each(function(){
			var obj_parent = $(this).parent();
			var obj_self = $(this);
			var but_click = $(this).find("p.selected");
			var domShow = $(this).find(".ps");
			var changeTxt = $(this).find(".selected span");
	
			obj_addoption = obj_parent.clone().insertAfter(obj_parent);
			bind_option1(true);
			obj_self.css('width', '86px').css('z-index', 'auto');
			obj_parent.addClass('valid'); // 标记选择组有效
			if($('div.valid').size() >= maxoption){
				obj_addoption.hide();
			}
			but_click.attr('data-filter', data_filter);
			obj_current = obj_parent;
			bind_option2(val);
			changeTxt.text(domShow.find('p[data-filter='+data_filter+']').find('span').text());
		});
	}
	
	function SetOptions(){
		var url = location.href; 
	    var paraString = url.substring(url.indexOf("?")+1,url.length).split(/&|#/);
	    var paraObj = {} 
	    for (i=0; i < paraString.length; ++i){
	    	if(paraString[i].indexOf("=") < 0)
	    		continue;
	    	paraObj[paraString[i].substring(0,paraString[i].indexOf("=")).toLowerCase()] = 
	    		paraString[i].substring(paraString[i].indexOf("=")+1,paraString[i].length);
	    }
	    var searchkw = paraObj['searchkw']; 
	    if(searchkw){
	    	searchkw = decodeURIComponent(searchkw);
	    	searchkw = searchkw.replace(/\+/g, ' ');
	    	$("input[name='searchkw']").val(searchkw);
	    }
	    
	    var filter = paraObj['filter'];
	    if(filter){
	    	filter = decodeURIComponent(filter);
	    	var options = filter.split('&');
	    	for(i=0; i<options.length; ++i){
	    		if(options[i].indexOf("=") < 0){
	    			continue;
	    		}
	    		var key = options[i].substring(0, options[i].indexOf("=")).toLowerCase();
	    		var val = options[i].substring(options[i].indexOf("=")+1,options[i].length).toLowerCase();
	    		if(key){
	    			switch(key){
	    			case 'cost':
	    			case 'atk':
	    			case 'life':
	    			case 'cardset':
	    			case 'cardrace':
	    			case 'class':
	    			case 'cardtype':
	    				dobind_option1(key,val);
	    				break;
	    			case 'cardrarity':
	    				var rarity = val.split(':');
	    				for(j=0; j<rarity.length; ++j)
	    				{
	    					$('div.s2_1 > ul li span').eq(parseInt(rarity[j])-1).attr('class','checked');
	    				}
	    				break;
	    			case 'cardpower':
	    				var power = val.split(':');
	    				for(j=0; j<power.length; ++j)
	    				{
	    					$('div.s2_2 > ul li span').eq(parseInt(power[j])-1).attr('class','checked');
	    				}
	    				break;
	    			default:
	    				break;
	    			}
	    		}
	    	}//end for
	    }//end filter
	    
	    var pageindex = paraObj['page'];
	    var ob = paraObj['ob'];
	    if(pageindex || ob || filter){
	    	document.documentElement.scrollTop = $('#searchForm').offset().top;
			document.body.scrollTop = $('#searchForm').offset().top;
	    }
	}
	
	SetOptions();
	
	$.post('main/getnews',null, function(data){
		if(data){
			lis = '';
			titleLen = 14;
			digestLen = 40;
			for(i=0; i<3 && i<data.length; ++i){
				if(data[i].title.length > titleLen){
					data[i].title = data[i].title.substr(0, titleLen);
				}
				if(data[i].digest.length > digestLen){
					data[i].digest = data[i].digest.substr(0, digestLen);
					data[i].digest += '...';
				}
				lis += '<li><p><span><a href="' + data[i].url + '" target="_blank">' + data[i].title + ' ' + data[i].posttime + '</a></span></p>';
				lis += '<p>' + data[i].digest + '</p></li>';
			}
			$("#newslist").html(lis);
		}
	},'json');
	
})