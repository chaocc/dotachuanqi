<?php
header("Content-type: text/html; charset=utf-8"); 
defined('BASEPATH') OR exit('No direct script access allowed');
// 抓取类

class Script extends CI_Controller{
		
		public function __construct()
		{
			parent::__construct();
			$this->Env_model->init_env();
			$this->load->model('hero_model');
			$this->load->model('skill_model');
			$this->load->model('hero_skill_model');
			$this->load->model('goods_model');
			$this->load->model('goods_hero_model');
		}
		public function index()
		{
			$contents = file_get_contents('http://dtcq.gamedashi.com/cards/index.html'); 
			preg_match_all('/<li class=\"class_all place_all (.*) (.*)\"><a href=\"\/cards\/(.*).html\"><img src=\"(.*)\" \/><\/a><span>(.*)<\/span><\/li>
/',$contents,$match);
			$array2=array();
			foreach($match[3] as $key =>$v)
			{
			if($match[1][$key]=='class_811')
			{
				$array2[$v]['class']='力量';
				}else if($match[1][$key]=='class_812'){
				$array2[$v]['class']='敏捷';
				}else if($match[1][$key]=='class_813'){
				$array2[$v]['class']='智力';
				}
			
			if($match[2][$key]=='place_1')
			{
				$array2[$v]['place']='前排';
				}else if($match[2][$key]=='place_2'){
				$array2[$v]['place']='中排';
				}else if($match[2][$key]=='place_3'){
				$array2[$v]['place']='后排';
				}
			
			}
			preg_match_all('/<li class=\"(.*)\"><a href=\"\/cards\/([a-z0-9_]+).html\"><img src=\"(.*)\" \/><\/a><span>([^<>]+)<\/span><\/li>/', $contents, $match_0);
			foreach($match_0[2] as $key =>$v){
				$content1 = file_get_contents('http://dtcq.gamedashi.com/cards/'.$v.'.html');
				//$content1 = file_get_contents('http://dtcq.gamedashi.com/cards/1.html');
				$this->get_hero_content($content1,$v,$key,$array2);
			}
		}
		
		public function get_hero_content($content1,$v,$i,$array2)
		{
		
			preg_match('/<h3><span>([^<>]+)<\/span>/',$content1,$heroName);
			$heroName=$heroName[1];  //英雄名字
			preg_match_all('/<p><span>([^<>]+)<\/span>([^<>]+)<\/p>/',$content1,$match_1);
			$description = $match_1[2][0]; // 英雄描述
			$tujin=$match_1[2][1];  //获取途径
			preg_match_all('/<div class=\"img-l\"> <img src=\"(.*)\" \/><\/div>/',$content1,$match_2);
			
			preg_match_all('/<th>(.*)：(.*)<\/th>/',$content1,$match_3);
			preg_match('/<th class=\"last\">(.*)：(.*)<\/th>/',$content1,$match_5);
			foreach($match_3[0] as &$p)
			{
				$p = strip_tags($p);
			}
			array_push($match_3[0],strip_tags($match_5[0]));
			$attributes =implode(',',$match_3[0]);
			$heroImg = $v.'.jpg';
			preg_match_all('/<img src=\"\/css\/news\/images\/csx-mmds.png\" \/>/',$content1,$match2_0);
			$star=count($match2_0[0]);
			$params=array();
			$params['id']=$v;
			$params['place']=$array2[$v]['place'];
			$params['class']=$array2[$v]['class'];
			$params['hero_name']=$heroName;
			$params['hero_img']="http://10.1.2.21/upload/hero_img/".$heroImg;
			$params['attributes']=$attributes;
			$params['star']=$star;
			$params['description']=$description;
			$params['tujin'] =$tujin;
			$goods=$this->test($v);
			$params['goods'] = $goods;
			PTrace($params);
			$ret = $this->hero_model->save_hero($params);
			$filename=IMGPATH.'heroImg'.DIRECTORY_SEPARATOR.$v.'.jpg';
			
			$this->GrabImage($match_2[1][0],$filename);

			//技能图片
			preg_match_all('/<li><div class=\"img-l\"><img src=\"(.*)\" \/><\/div>/',$content1,$match_6);
			// 技能名称、技能描述、技能加点说明
			preg_match_all('/ <div class=\"tuv-du\"><span>(.*)<\/span><b class=\"(.*)\">(.*)<\/b><\/div>
                        <p>(.*)<\/p>
/',$content1,$match_7);
			$skillIDs=array();
			
			foreach($match_6[1] as $key=>$r){
				$skillId = $i*4+($key+1);
				array_push($skillIDs,$skillId);
				$skillImg=$skillId.'.jpg';
				if(isset($match_7[1][$key]) && $match_7[1][$key]){
					$skillName=$match_7[1][$key];
				}
				if(isset($match_7[3][$key]) && $match_7[3][$key]){
					$skillAdd = $match_7[3][$key];
				}
				if(isset($match_7[4][$key]) && $match_7[4][$key]){
					$skillDescription=$match_7[4][$key];
				}
				$filename=IMGPATH.'skillImg'.DIRECTORY_SEPARATOR.$skillImg;
				
				$params=array();
				$params['id']=$skillId;
				$params['skill_name']=$skillName;
				$params['skill_add']=$skillAdd;
				$params['description']=$skillDescription;
				$params['skill_img']=$skillImg;
				$this->GrabImage($match_6[1][$key],$filename);
			   $skill_ret= $this->skill_model->save($params);
			}
	
			$skillIDs=implode(',',$skillIDs);
			$params2['hero_id']=$v;
			$params2['skill_id']=$skillIDs;
			 $this->hero_skill_model->save($params2);
		}
		
		function GrabImage($url,$filename="") {
			if($url==""):return false;endif;
			if($filename=="") {
			$ext=strrchr($url,".");
			if($ext!=".gif" && $ext!=".jpg"):return false;endif;
			$filename=date("dMYHis").$ext;
			}
			ob_start();
			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
			$img = curl_exec($ch);
			ob_end_clean();
			$size = strlen($img);
			$fp2=@fopen($filename, "w+");
			fwrite($fp2,$img);
			fclose($fp2);
			return $filename;
		}
		
		public function get_wupin()
		{
			$contents = file_get_contents('http://dtcq.gamedashi.com/itemlist/index.html'); 
			preg_match_all('/<li class=\"sus type_all  (.*) (.*)\">
                    	<a href=\"(.*)\">
                    	<div class=\"myy-cs\">
                            <img  src=\"(.*)\"\/>
                            <div class=\"myy-im-s\"><\/div>
                        <\/div>
                        <span>(.*)<\/span> 
                        <\/a>
                    <\/li>
/',$contents,$content1);
			
			$array=array();
			foreach($content1[3] as $key =>$v){
				$v=str_replace('/itemlist/','',$v);
				$v=str_replace('.html','',$v);
				if($content1[1][$key]=='color_1')
				{
					$array[$v]['level']='白色';
				}else if($content1[1][$key]=='color_2'){
					$array[$v]['level']='绿色';
				}else if($content1[1][$key]=='color_3'){
					$array[$v]['level']='蓝色';
				}else if($content1[1][$key]=='color_4'){
					$array[$v]['level']='紫色';
				}else if($content1[1][$key]=='color_5'){
					$array[$v]['level']='橙色';
				}
				
				if($content1[2][$key]=='type_1')
				{
					$array[$v]['type']='零件';
				}else if($content1[2][$key]=='type_2'){
					$array[$v]['type']='合成品';
				}else if($content1[2][$key]=='type_3'){
					$array[$v]['type']='消耗品';
				}else if($content1[2][$key]=='type_4'){
					$array[$v]['type']='卷轴';
				}
				
			}
			foreach($content1[3] as $key =>$v){
				$url='http://dtcq.gamedashi.com/'.$v;
				//$url='http://dtcq.gamedashi.com/itemlist/16.html';
				$content2=file_get_contents($url);
				$v=str_replace('/itemlist/','',$v);
				$v=str_replace('.html','',$v);
				preg_match('/<h3>
                <span class=\"(.*)\">(.*)<em>(.*)<\/em><\/span> 
                <p><b class=\"x-gold\">(.*)<\/b> \| (.*)<\/p>
                <\/h3>/',$content2,$rs1);
				$isFlag=1;
				if(!$rs1){
						$isFlag=0;
									preg_match('/<h3>
                <span class=\"(.*)\">(.*)<\/span> 
                <p><b class=\"x-gold\">(.*)<\/b> \| (.*)<\/p>
                <\/h3>/',$content2,$rs1);
				}
			
				//物品名称
				$goodsName=$rs1[2];
				
				// 等级要求
				if($isFlag==1)
				{
					$goodsDengji=str_replace('（','',$rs1[3]);
					$goodsDengji=str_replace('）','',$goodsDengji);
				}
				
				if($isFlag==1)
				{
				//金额
					$gold=$rs1[4];
					// 说明
					$description=$rs1[5];
				}else{
					$gold=$rs1[3];
					$description=$rs1[4];
				}
				PTrace($gold);
				PTrace($description);
				die();
				// 装备效果
				preg_match_all('/<td> ([^<>]+)<\/td>/',$content2,$rs2);
				$xiaoguo=implode(',',$rs2[1]);
				
				//获取途径
				preg_match_all('/<td>([^<>]+)<\/td>/',$content2,$rs3);
				$length1=count($rs2[1])-1;
				$length2=count($rs3[1])-1;

				
				$rs8=array();
				for($i=$length1+1;$i<=$length2;$i++)
				{
					array_push($rs8,$rs3[1][$i]);
				}
				$rs8=implode(',',$rs8);
				
				
				preg_match_all('/<table[^>]+>(.*)<\/table>/isU',$content2,$rs5);
				if(isset($rs5[0][0])){
				preg_match_all('/<a href=\"\/itemlist\/(.*).html\">
                    <div class=\"myy-cs\">
                	<img  src=\"http:\/\/dtcq.gamedashi.com\/images\/itemicon\/(.*).jpg\"\/>
                    <div class=\"myy-im-s\"><\/div>
                	<\/div>
                    <\/a>
                    <span>(.*)<\/span>/',$rs5[0][0],$rs6);
				}
					if(isset($rs5[0][1])){
									preg_match_all('/<a href=\"\/itemlist\/(.*).html\">
                    <div class=\"myy-cs\">
                	<img  src=\"http:\/\/dtcq.gamedashi.com\/images\/itemicon\/(.*).jpg\"\/>
                    <div class=\"myy-im-s\"><\/div>
                	<\/div>
                    <\/a>
                    <span>(.*)<\/span>/',$rs5[0][1],$rs7);
					}
			$tobe=$be='';
			if(isset($rs6) && count($rs6)>1)
			{
				//可合成
				$tobe=implode(',',$rs6[1]);
			}
			
			if(isset($rs7) && count($rs7)>1)
			{
				//合成
				$be=implode(',',$rs7[1]);
			}
				$params['id']=$v;
				$params['goods_name']=$goodsName;
				$params['goods_dengji']=$goodsDengji;
				$params['golds']=$gold;
				$params['description']=$description;
				$params['xiaoguo']=$xiaoguo;
				$params['tujin']=$rs8;
				$params['tobe']=$tobe;
				$params['be']=$be;
				$params['color']=$array[$v]['level'];
				$params['type']=$array[$v]['type'];
				$params['goods_img'] = 'http://dotachuanqi.com/upload/goods_img/'.$v.'.jpg';
				$this->goods_model->save($params);
				$filename=IMGPATH.'goodsImg'.DIRECTORY_SEPARATOR.$v.'.jpg';
			$this->GrabImage($content1[4][$key],$filename);
				if(isset($rs5[0][2])){
					preg_match_all('/<a href=\"\/cards\/(.*).html\">
                    <div class=\"her-box\">
                	<img  src=\"(.*)\"\/>
                    <div class=\"her-box-bor(.*)\"><\/div>
                	<\/div>
                	<span class=\"(.*)\">([^<>]+)<\/span>
                    <\/a>
/',$rs5[0][2],$rs9);
				}else if(isset($rs5[0][1])){
									preg_match_all('/<a href=\"\/cards\/(.*).html\">
                    <div class=\"her-box\">
                	<img  src=\"(.*)\"\/>
                    <div class=\"her-box-bor(.*)\"><\/div>
                	<\/div>
                	<span class=\"(.*)\">([^<>]+)<\/span>
                    <\/a>
/',$rs5[0][1],$rs9);
				}
				if(isset($rs9) && $rs9){
					$params3['goods_id']=$v;
					$params3['hero_id']=implode(',',$rs9[1]);

					$params3['message']=implode(',',$rs9[5]);
				  $this->goods_hero_model->save($params3);
				}
				
			}
		}
			public function test($v)
			{
				$search = '/<ul>(.*?)<\/ul>/is';
				$url='http://dtcq.gamedashi.com/cards/'.$v.'.html';
				$content=file_get_contents($url);
				preg_match_all($search,$content,$match);
				$content2=str_replace('<li>','</li>
				<li>',$match[0][2]);
			$content2=str_replace('</a>
                </ul>','</a></li>',$content2);
				preg_match_all('/<li>(.*?)<\/li>/is',$content2,$content3);
				$array3='';
				foreach($content3[0] as $key=>$v)
				{
					preg_match('/<span class=\"(.*)\">(.*)<\/span>/is',$v,$content5);
					preg_match_all('/<img  src=\"http:\/\/dtcq.gamedashi.com\/images\/itemicon\/(.*).jpg"\/>
/',$v,$content6);
				
					$content6=implode('|',$content6[1]);
					if($key!=count($content3[0])-1){
						$array3=$array3.$content5[2].':'.$content6.',';
					}else{
						$array3=$array3.$content5[2].':'.$content6;
					}
					
				}
				
				return $array3;
			}
			
			public function hero_img()
			{
				$url = 'http://d.longtugame.com/daotadata/allhero';
				$contents = file_get_contents($url);
				preg_match_all('/                            <a href=\"(.*)\">
                                <img src=\"(.*)\" alt=\"\" \/>
/',$contents,$content1);
				foreach($content1[2] as $key =>$v)
				{
					$filename=IMGPATH.'heroImg'.DIRECTORY_SEPARATOR.($key+1).'.jpg';

					$this->GrabImage($v,$filename);
				}
			}

}