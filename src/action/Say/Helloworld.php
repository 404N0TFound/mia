<?php
namespace mia\miagroup\Action\Say;
class Helloworld extends \FW_Action {
    public function execute() {
//         $sayService = new \mia\miagroup\Service\Say();
//         $result = $sayService->hello('jack');
//         var_dump($result);exit;
//        echo date("Y-m-d H:i:s",time());
//        $subjectService = new \mia\miagroup\Service\Album();
//        $result = $subjectService->getArticleList('1000127', 12);
//        $result = $subjectService->getRecommendAlbumArticleList();
        var_dump(!(int)$user_id);die;
        $subjectService = new \mia\miagroup\Service\Subject();
        $result = $subjectService->getBatchSubjectInfos(array('11082','11082'));
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
