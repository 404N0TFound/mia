<?php
namespace mia\miagroup\Action\Say;
class Helloworld extends \FW_Action {
    public function execute() {
        $userModel = new \mia\miagroup\Model\User();
        $uinfo = $userModel->getRow(array('id', '1'), 'id, name, location');
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
        ));
    }
}
