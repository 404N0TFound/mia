<?php
namespace mia\miagroup\Data\Subject;

use Ice;

class SubjectBlog extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_blog_info';
    
    /**
     * 新增长文帖子
     */
    public function addBlog($insert_data) {
        if (empty($insert_data)) {
            return false;
        }
        foreach ($insert_data as $k => $v) {
            if (in_array($k, ['index_cover_image', 'blog_meta', 'ext_info'])) {
                $insert_data[$k] = json_encode($v);
            }
            if ($k == "status") {
                $insert_data[$k] = $v;
            }
        }
        $insert_id = $this->insert($insert_data);
        return $insert_id;
    }
    
    /**
     * 更新长文帖子
     */
    public function updateBlogBySubjectId($subject_id, $update_data) {
        if (empty($subject_id) || empty($update_data) || !is_array($update_data)) {
            return false;
        }
        $set_data = array();
        foreach ($update_data as $k => $v) {
            if (in_array($k, ['index_cover_image', 'blog_meta', 'ext_info'])) {
                $v = json_encode($v);
            }
            $set_data[] = array($k, $v);
        }
        $where[] = array('subject_id', $subject_id);
        $data = $this->update($set_data, $where);
        return $data;
    }

    /**
     * 批量查长文信息
     */
    public function getBlogBySubjectIds($subject_ids, $status = array(1)) {
        if (empty($subject_ids)) {
            return array();
        }
        $where = array();
        $where[] = array('subject_id', $subject_ids);
        if (!empty($status)) {
            $where[] = array('status', $status);
        }
        $subjects = $this->getRows($where);
        if (empty($subjects)) {
            return array();
        }
        $result = array();
        foreach ($subjects as $v) {
            if (!empty($v['index_cover_image'])) {
                $v['index_cover_image'] = json_decode($v['index_cover_image'], true);
            }
            if (!empty($v['ext_info'])) {
                $v['ext_info'] = json_decode($v['ext_info'], true);
            }
            $v['blog_meta'] = json_decode($v['blog_meta'], true);
            $result[$v['subject_id']] = $v;
        }
        return $result;
    }
    
    /**
     * 获取用户发布的长文列表
     */
    public function getSubjectBlogLists($where = [], $page = 1, $limit = 20, $status = [1]){
        $offset = $limit * ($page - 1);
        
        if(!empty($where)){
            if(isset($where['user_id'])){
                $where[] = ['user_id', $user_id];
            }
        }
        
        if (!empty($status)) {
            $where[] = ['status', $status];
        }
        $order_by = 'subject_id DESC';
        $data = $this->getRows($where, 'subject_id', $limit, $offset, $order_by);
        $result = [];
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['subject_id'];
            }
        }
        return $result;
    }

    public function getLastBlog($userIds, $num = 1)
    {
        $user_str = implode(",",$userIds);

        $sql = <<<EOD
SELECT
	a.subject_id
FROM
	group_subject_blog_info a
WHERE
    a.user_id IN ({$user_str})
AND
	{$num} > (
		SELECT
			COUNT(*)
		FROM
			group_subject_blog_info b
		WHERE
		b.user_id IN ({$user_str})
		AND	b.user_id = a.user_id
		AND b.create_time > a.create_time
	)
ORDER BY
	a.create_time DESC
EOD;
        $res = $this->query($sql);
        if(empty($res)) {
            return [];
        }
        $return = [];
        foreach ($res as $v) {
            $return[] = $v["subject_id"];
        }
        return $return;
    }
}