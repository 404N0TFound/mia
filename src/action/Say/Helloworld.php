<?php
namespace mia\miagroup\Action\Say;
class Helloworld extends \FW_Action {
    public function execute() {
//         $sayService = new \mia\miagroup\Service\Say();
//         $result = $sayService->hello('jack');
//         var_dump($result);exit;
//        echo date("Y-m-d H:i:s",time());
//        echo json_encode(array('url'=>'/d1/p3/2016/05/11/dc/ae/dcaee5986f03730a88503f19e3f863dc.jpg','width'=>80,'height'=>30,'content'=>666));
        $subjectService = new \mia\miagroup\Service\Album();
//        $result = $subjectService->getArticleList('1508587', 4);
//        $result = $subjectService->getAlbumNum(array('1508587'));
//        $result = $subjectService->getRecommendAlbumArticleList();
//        var_dump(empty(0));die;
//        $subjectService = new \mia\miagroup\Service\Subject();
//        $result = $subjectService->getBatchSubjectInfos(array('11082','11082'));
        $subjectInfo = array(
 "article_id" => 20,   //文章ID发布完成后写表用
 "album_id" => 19,
 "user_id" => 1145319,
 "title" => 'testtesttest',
 "text" => 'testtes<a href="#">tte</a>sttesttest',
 "image_infos" => array(
    0 => array(
        'height' => 522, 
        'url' => '/d1/p4/2016/06/17/89/09/8909b7a9830d432c8b338363c9fae326542443173.jpg', 
        'width' => 480
    )
 ),
//  "active_id" => 0, 
  "video_url" => 'video/2016/05/04/089912967ba54274ec761531a7796eb3.mp4',
  "labels" => array(
    array(
        "id" => 4,
        "title" => "Allen"
    ),
    array(
        "id" => 5,
        "title" => "Jane"
    )
  )
);
        $result = $subjectService->pcIssue($subjectInfo);
//        $result = $subjectService->getArticlePreview(array('user_id'=>'778800','article_id'=>'20','album_id'=>'19'));
        echo "<pre>";
        print_r($result);exit;
        
        /*
        $client = $this->ice->mainApp->proxy_service->get('demo-local', 'Say');
        return $this->ice->mainApp->proxy_filter->get('(map){
            code(int);
            data(map){
                uid(int);
                uname(str);
                service(map){
                    code(int);
                    data(str);
                };
                user(map){
                    id(int);
                    name(str);
                    location(str);
                }
            }
        }')->filter(array(
            'code' => 0,
            'data' => array(
                'uid'   => 5012470,
                'uname' => 'goosman-lei',
                'service' => $client->hello('Jack'),
                'user'    => $uinfo,
            ),
        ));*/
    }
}
