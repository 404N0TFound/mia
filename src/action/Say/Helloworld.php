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
         $result = $subjectService->getArticleList('1508587', 4);
 //        $result = $subjectService->getAlbumNum(array('1508587'));
         $result = $subjectService->getRecommendAlbumArticleList();
 //        var_dump(empty(0));die;
 //        $subjectService = new \mia\miagroup\Service\Subject();
 //        $result = $subjectService->getBatchSubjectInfos(array('11082','11082'));
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