<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\DailyModel;

/**
* 
*/
class DailyController extends AdminBaseController
{
	
	public function _initialize() {

	}

	public function index(){

	}

	/**
	 * 日报
	 */
	public function addDaily() {
		$user = model('user');
		$jobnumber = $user->where('id','eq',session('ADMIN_ID'))->value('user_jobnumber');
		if(date('w') != 0){
			return $this->fetch('daily/dailyadd',[
				'number' => $jobnumber
				]);
		}else{
			return $this->fetch('daily/weekadd',[
				'number' => $jobnumber
				]);
		}
	}

	/**
	 * 日报管理页面
	 */
	public function Manage() {
		$firstDay = strtotime("previous monday");
		$users = model('user');
		$daily = model('daily');
		$user_member_id = $users->field('id,user_login')->select();
		foreach ($user_member_id as $key => $uid) {
			$count = $daily->where(['userid' => $uid['id']])->count();
			$uid['sumCount'] = $count;
			$weekCount = $daily->where(['userid' => $uid['id']])->where('daily_time','gt',$firstDay)->count();
			$uid['weekCount'] = $weekCount;
			$uid['jobnumber'] = $users->where(['id' => $uid['id']])->value('user_jobnumber');
			$person_info[] = $uid;
		}
		$this->assign('info',$person_info);
		return $this->fetch('daily/manage');
	}

	public function person(){
		$users = model('users');
		$daily = model('daily');
		$info1 = $users->field('daily.daily_id,users.user_login,users.user_jobnumber,daily.daily_content,daily.daily_time')->table(['cmf_users' => 'users','cmf_daily' => 'daily'])->where('users.id','eq','daily.userid')->order('daily_time desc')->select();
		$this->assign('info1',$info1);
		return $this->fetch('daily/personDaily');
	}

	/**
	 * 提交日报
	 */
	public function OpAdd() {
		$daily = model('daily');
		$users = model('user');
		$uid = $users->where('user_jobnumber','eq',input("post.number"))->value('id');
		$now = $daily->where('userid','eq',$uid)->max('daily_time')+68400;
		$userid = $users->where(array('user_jobnumber' => input("post.number")))->value('id');
		$daily_model = new DailyModel();
		if (date("G") >= 23||date("G") <= 13) {
			$this->error('时间不对。。。');
		}elseif ($now < time()){
			if($daily_model->add_Daily($userid,input("post.content"))){
				$this->success('提交成功！');
			}else{
				$this->error('提交失败，请重新填写日报。。。');
			}
		}else{
			$this->error('今日已提交完毕。。。');
		}
		
	}

	/**
	 * 周报
	 */
	public function OpWeek() {
		$daily = model('daily');
		$users = model('user');
		$uid = $users->where('user_jobnumber','eq',input("post.number"))->value('id');
		$now = $daily->where('userid','eq',$uid)->max('daily_time')+68400;
		$userid = $users->where(['user_jobnumber' => input("post.number")])->value('id');
		$content = input("post.point").'|'.input("post.content").'|'.input("post.summary").'|'.input("post.except");
		$daily_model = new DailyModel();
		// echo $content;die();
		if (date("G") >= 23||date("G") <= 18) {
			$this->error('时间不对。。。');
		}elseif ($now < time()){
			if($daily_model->add_Daily($userid,$content)){
				$this->success('提交成功！');
			}else{
				$this->error('提交失败，请重新填写日报。。。');
			}
		}else{
			$this->error('今日已提交完毕。。。');
		}
	}

	/**
	 * 自动生成周报并压缩为zip
	 */
	public function zip(){
		$userModel = model('user');
		$uidLists = $userModel->column('id');
		// $result = $this->mkWord(2);die();
		foreach ($uidLists as $uidList) {
			$result = $this->mkWord($uidList);
		}
		// dump($uidLists);die();
		$zip = new \ZipArchive();
		if ($zip->open("./data/Dailyzip/".date("Y-m-d").".zip", \ZIPARCHIVE::CREATE)===TRUE) {
			$this->addFileToZip("./data/Dailyfinal/" . date("Y-m-d") , $zip);
			// $this->success('周报已经成功生成');

			$zip->close();
		}
		$this->DownLoadZip('./data/Dailyzip/'.date("Y-m-d").'.zip');
		
	}

	public function addFileToZip($path, $zip)
	{
		$handler = opendir($path); //打开当前文件夹由$path指定。
        /*
        循环的读取文件夹下的所有文件和文件夹
        其中$filename = readdir($handler)是每次循环的时候将读取的文件名赋值给$filename，
        为了不陷于死循环，所以还要让$filename !== false。
        一定要用!==，因为如果某个文件名如果叫'0'，或者某些被系统认为是代表false，用!=就会停止循环
        */
        while (($filename = readdir($handler)) !== false) {
        	if ($filename != "." && $filename != "..") {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
        		if (is_dir($path . "/" . $filename)) {// 如果读取的某个对象是文件夹，则递归
        			$this->addFileToZip($path . "/" . $filename, $zip);
        		} else { //将文件加入zip对象
        			$zip->addFile($path . "/" . $filename);
        		}
    		}
		}
		@closedir($path);
	}

	

    /**
     * 下载zip
     */
    public function DownLoadZip($filename) {
    	header("Cache-Control: public"); 
    	header('Content-Type: application/octet-stream'); 
		header('Content-disposition: attachment; filename='.basename($filename)); //文件名  
		header("Content-Type: application/zip"); //zip格式的  
		header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件  
		header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小  
		header('Expires: 0'); 
		header('Cache-Control: must-revalidate');
		ob_clean(); 
		flush();
		@readfile($filename);
		exit();
	}

	/**
	 * 生成word文档
	 */
	public function mkWord($uid) {// 
		$Monday = strtotime("last Sunday") - 518400;
		$Sunday = strtotime("last Sunday") + 86400;
		// echo date("Y-m-d",$Monday).'||'.date("Y-m-d",$Sunday);die();
		$daily = model('daily');
		$users = model('user');
		$dailyModel = new DailyModel();
		$map['daily_time'] = [['gt', $Monday],['lt', $Sunday]];
		$user_id_name = $users->field('id,user_login')->where('id','eq',$uid)->find();
		// dump($user_id_name);die();
		$supp = $daily->where('userid','eq',$uid)->where($map)->order('daily_time desc')->value('daily_content');
		// $contain = $daily->field('daily_content,daily_time')->where('userid','eq',$user_id_name['id'])->where($map)->order('daily_time desc')->select();
		// dump($contain);die();
		if (strpos($supp,'|')) {
			list($point,$content,$summary,$except) = explode('|', $supp);
		}else{
			$point = $content = $summary = $except = "空";
		}
		// dump($supp);die();
		// echo $point.','.$content.','.$summary.','.$except;die();
		$word="<h1 align=\"center\">工作周报</h1>
		<p />
		<p>时　间：".date("Y年m月d日",$Monday)."至".date("Y年m月d日",$Sunday-86400)."</p>
		<p>姓　名：".$user_id_name['user_login']."</p>
		<p />
		<p>一、工作重点：</p>
		<p>　　".$point."</p>
		<p />
		<p>二、具体内容： </p>";
		$timeListStart = $Monday;
		$timeListEnd = $timeListStart+86400;
		for ($i=0; $i < 7 ; $i++) { 
			$info = date("m月d日：" ,$timeListStart);
			$infos = $dailyModel->is_ContentEmpty($uid,$timeListStart,$timeListEnd);// $user_id_name['id']
			if(empty($infos)){
				$info = "<p>".$info."空</p>";
			}elseif (date("w" ,$timeListStart) == 0) {
				$info = "<p>".$info.$content."</p>";
			}else{
				$info = "<p>".$info.$infos['daily_content']."</p>";
			}
			$word = $word.$info;
			$timeListStart = $timeListStart + 86400;
			$timeListEnd = $timeListEnd + 86400;
		}
		$word = $word."<p />
		<p>三、本周工作总结 </p>
		<p>　　".$summary."</p>
		<p />
		<p>四、下周工作安排 </p>
		<p>　　".$except."</p>
		<p />";
		// echo $word;die();
		$result = $this->cword($word,iconv("UTF-8","GB2312//IGNORE",$user_id_name['user_login']));
		$result="success".$result;
		return $result;
	}
	

	function cword($data,$fileName=''){
		if(empty($data)) return '';
		$data='<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'.$data.'</html>';
		$dir = "./data/Dailyfinal/".date("Y-m-d")."/";
		if(!file_exists($dir)) mkdir($dir,0777,true);
		if(empty($fileName)) {
			$fileName=$dir.date('His').'.doc';
		}else{
			$fileName =$dir.$fileName.'.doc';
		}
		$writefile=fopen($fileName,'wb') or die("创建文件失败");//wb以二进制写入
		fwrite($writefile,$data);
		fclose($writefile);
		return $fileName;
	}
}