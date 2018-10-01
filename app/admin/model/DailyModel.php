<?php

namespace app\admin\model;

use think\Model;

/**
* 
*/
class DailyModel extends Model
{


	public function add_Daily($userid,$content) {
		$this->data([
			'userid' => $userid,
			'daily_content'  => $content,
			'daily_time' => time()
			]);
		return $this->isUpdate(false)->save();
	}

	/**
	 * 判断当天是否有日报内容
	 * @param  [type]  $uid       [description]
	 * @param  [type]  $startTime [description]
	 * @param  [type]  $endTime   [description]
	 * @return boolean            [description]
	 */
	public function is_ContentEmpty($uid,$startTime,$endTime)
	{
		return $this->where('daily_time','BETWEEN',[$startTime,$endTime])->where(['userid' => $uid])->find();
	}
}