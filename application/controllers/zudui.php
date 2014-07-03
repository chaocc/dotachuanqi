<?php
header("Content-type: text/html; charset=utf-8"); 

//组队算法

class Zudui extends CI_Controller{

		public function __construct()
	{
		parent::__construct();
		$this->Env_model->init_env();
		$this->load->model('hero_team_model');
		$this->load->model('hero_model');
	}
	
	public function index()
	{
		$result = array();
		$rs = $this->hero_team_model->select();
		foreach($rs as $k =>$v)
		{
			$result[$k]['hero_id']= $v->hero_id;
			$result[$k]['type']=$v->type;
		}
		
		$result2 = json_encode($result);
		file_put_contents(FCPATH.'/data/hero_team.inc',$result2);
		/*
		$result = file_get_contents(FCPATH.'/data/hero_team2.inc');
		$result  = json_decode($result,true);*/
		$hero = $this->hero_model->select();
		$zudui_tt = $zudui_yz = array();
		$hero_array = array();
		foreach($hero as $k=>$v)
		{
			foreach($result as $k1 =>$p)
			{
				if(in_array($v->id,explode(',',$p['hero_id'] ))  &&  $p['type']=='推图阵容')
				{
					$zudui_tt[$v->id][]=intval($k1);
					
				}if(in_array($v->id,explode(',',$p['hero_id'] ))  &&  $p['type']=='远征阵容')
				{
					$zudui_yz[$v->id][]=intval($k1);
				}
			}

			$hero_array[$v->id]['type'] = $v->class;
			$hero_array[$v->id]['sort'] = intval($v->sort);
		}

		$hero_array = json_encode($hero_array);
		file_put_contents(FCPATH.'/data/hero.inc',$hero_array);
		foreach($zudui_tt as $k=>&$q)
		{
			$q = implode(',',$q);
		}
		$zudui_tt = json_encode($zudui_tt);
		file_put_contents(FCPATH.'/data/zudui_tt.inc',$zudui_tt);
		
		foreach($zudui_yz as $k=>&$q)
		{
			$q = implode(',',$q);
		}
		$zudui_yz = json_encode($zudui_yz);
		file_put_contents(FCPATH.'/data/zudui_yz.inc',$zudui_yz);
	}
	
	public function get_zudui()
	{
		$params = array(3,1,33,4,15,44,40,17,33);
		asort($params);
		$result = $this->hero_team_model->get_zudui($params);
		PTrace($result);
	}
	
	public function test()
	{
		$arr = range(15,45); 
		$t = self::getCombinationToString($arr, 5); 

		$t = json_encode($t);
		file_put_contents(FCPATH.'/data/hero_team2.inc',$t);
	}

function getCombinationToString($arr,$m)
{
    $result = array();
    if ($m ==1)
    {
       return $arr;
    }
    
    if ($m == count($arr))
    {
        $result[] = implode(',' , $arr);
        return $result;
    }
        
    $temp_firstelement = $arr[0];
    unset($arr[0]);
    $arr = array_values($arr);
    $temp_list1 = self::getCombinationToString($arr, ($m-1));
    
    foreach ($temp_list1 as $s)
    {
        $s = $temp_firstelement.','.$s;
        $result[] = $s; 
    }
    unset($temp_list1);

    $temp_list2 = self::getCombinationToString($arr, $m);
    foreach ($temp_list2 as $s)
    {
        $result[] = $s;
    }    
    unset($temp_list2);
    
    return $result;
}

}