var $lsdb_card = {};
var $lsdb_cardset = {};
var $lsdb_flagshow = true;
var $lsdb=new function(){
	this.generate_cardset = function (dom, cardset) {
		if( ! cardset)return;
		var cardset_html = '';
		for(var i = 0; i <= 8; ++i) {
			if(cardset[i]) {
				var cost = i<8?i:'7+';
				cardset_html +='<p><span class="cardset_cost"><strong>法力值消耗 '+cost+': </strong></span>'
				for(var j=0; j<cardset[i].length; ++j) {
					cardset_html += '<span class="cardset_card"><strong><a href="'+cardset[i][j]['card_url']+'" target="_blank">';
					cardset_html += cardset[i][j]['card_name']+'</a> x '+cardset[i][j]['number']+' </strong></span>';
				}
				cardset_html +='</p>'
			}
		}
		dom.html(cardset_html);
	};
	
	this.get_id = function (url) {
		var pos = url.lastIndexOf('/');
		if(pos >= 0) {
			return url.substr(pos+1);
		}
		else {
			return false;
		}
	};
	
	this.hoverImg = function(e,t,img){
		e = e||window.event;
		var _$this = t,
			_img = jQuery(".hovnoImg[data-imgHov='Img']"),
			_lft = _$this.offset().left+_$this.outerWidth()+10,
			_top = _$this.offset().top+22,
			_x = e.pageX>_lft?e.pageX:_lft,
			_y = e.pageY>_top?e.pageY:_top,
			_scrollTop = jQuery(window).scrollTop(),
			_winHeight = jQuery(window).height();
		_img.css({"width":"168px","height":"236px"});
		if(_y-_scrollTop>_winHeight/2){
			_y = _y-_img.height()	
		}
		_img.stop().attr({
			"src":img
		}).css({
			"left":_x,
			"top":_y
		}).fadeIn(100,function(){
			jQuery(this).css({"opacity":1});
		});
	};
	
	this.bind_single_card = function (filter){
		jQuery(filter).bind("mouseenter",function(e){
			var file = jQuery(this).attr('href');
			var cid = $lsdb.get_id(file);
			file = file.replace('main/card', 'api/cardjs');
			var dom = jQuery(this);
			$lsdb_flagshow = true;
			if (file && cid) {
				var fsuccess = function(){
					window.setTimeout(function(){
						if($lsdb_flagshow && $lsdb_card[cid]){
							$lsdb.hoverImg(e,dom,$lsdb_card[cid].image);		
						}
					},200);
				};
				if($lsdb_card[cid]) {
					fsuccess();
				}
				else {
					$lsdb.loadjs(file, fsuccess);
				}
			}
		});
		jQuery(filter).bind("mouseleave",function(){
			var _$this = jQuery(this);
			$lsdb_flagshow = false;
			jQuery(".hovnoImg[data-imgHov='Img']").hide().attr("src","");
		});
	};
	
	this.loadjs = function (file, fsuccess) {
		jQuery.ajax({
			url: file,
			dataType: 'script',
			cache: true,
			async: false,
			success: fsuccess
		});
	};
}

jQuery('document').ready(function(){
	jQuery("body").append("<img src=\"\" class=\"hovnoImg\" data-imgHov=\"Img\"/>");
	jQuery('.cardset').each(function(){
		var file = jQuery(this).attr('data-url');
		var cid = $lsdb.get_id(file);
		file = file.replace('main/cardset', 'api/cardsetjs');
		var dom = jQuery(this);
		if (file && cid) {
			var fsuccess = function(){$lsdb.generate_cardset(dom, $lsdb_cardset[cid]);$lsdb.bind_single_card('.cardset a');};
			if($lsdb_cardset[cid]) {
				fsuccess();
			}
			else {
				$lsdb.loadjs(file, fsuccess);
			}
		}
	});
	$lsdb.bind_single_card('.single_card');
});