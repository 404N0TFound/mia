<?php
namespace mia\miagroup\Daemon\Live;


use mia\miagroup\Util\QiniuUtil;
use mia\miagroup\Service\Subject;
use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Data\Live\Live as LiveData;

/**
 *把直播回放移到视频资源中，关发表回放帖子
 *
 * 
 */
class Livetovideo extends \FD_Daemon
{
	

	public function execute()
	{
		$qiniu = new QiniuUtil();
		$subject = new Subject();
		$liveModel = new LiveModel();

		while (1) {
			// 获取直播已经结束的直播流id
			$lives = $this->getLives();
			if (!$lives) {
				echo "全部更新完毕."."\n";
                exit;
			}

			foreach ($lives as $k => $v) {
				// m3u8转换成MP4
				$tomp4 = $qiniu->getSaveAsMp4($v['stream_id']);
				if (!isset($tomp4['targetUrl']) || empty($tomp4['targetUrl'])) {
					echo 'm3u8转换成MP4失败'."\n";
					continue;
				}

				// 从七牛mia_live-live移到video资源目录下
				$mvToVideo = $qiniu->fetchBucke($tomp4['targetUrl']);
				if (!isset($mvToVideo['key']) ||  empty($mvToVideo['key'])) {
					echo '资源移动失败'."\n";
					continue;
				}

				// 重命名文件
				$renameVideo = $qiniu->rename('video',$mvToVideo['key'],$tomp4['fileName']);
				if (!$renameVideo) {
					echo '重命名文件失败'."\n";
					continue;
				}

				//发帖子
				$subjectInfo['user_info'] = [
					'user_id' => $v['user_id'],
				];
				$subjectInfo['video_url'] = $tomp4['fileName'];
				$result                   = $subject->issue($subjectInfo);
				if (!$result) {
					echo '发帖子失败 直播 id is '.$v['id']."\n";
					continue;
				}


				// 更新直播
				$liveInfo[] = ['subject_id', $result['id']];
				$res        = $liveModel->updateLiveById($v['id'], $liveInfo);
				if (!$res) {
					echo '更新失败 直播 id is '.$v['id']."\n";
					continue;
				}

			}

			sleep(1);

		}
		

	}

	/**
	 * 获取subject_id=0直播结束的数据
	 *
	 * @return void
	 * @author 
	 **/
	public function getLives()
	{
		$live    = new LiveData();
		$where[] = ['subject_id',0];
		$where[] = ['status',4];
		$result  = $live->getRows($where,'*',1);
		return $result;
	}
}