<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MDiscoverFlow;

class Youdu extends ApiBase {

    public function what() {
        return 'Bochara';
    }

    public function rights() {
        return "有读书房拥有本微信小程序内所有资料的版权，任何被授权的浏览、复制、打印和传播属于本网站内的资料必须符合以下条件：\n"
            ."（1）所有的资料和图像均以获得信息为目的；\n"
            ."（2）所有的资料和图像均不得用于商业目的；\n"
            ."（3）所有的资料、图像及其任何部分都必须包括此版权声明；"
            ."\n本微信小程序所有的产品、技术与所有程序均属于有读书房知识产权，在此并未授权。"
            ."\n未经有读书房许可，任何人不得擅自（包括但不限于：以非法的方式传播、展示、镜像等）使用，或通过非常规方式（如：恶意干预有读书房数据）影响书房的正常服务，任何人不得擅自以软件程序自动获得有读书房数据。否则，将依法追究法律责任。";
    }

    public function legals() {
        return "有读书房提醒您：在使用有读书房前，请您务必仔细阅读并透彻理解本声明。您使用有读书房，您的使用行为将被视为对本声明全部内容的认可。\n"
            ."（1）有读书房仅为用户发布的内容提供存蓄空间及借阅展示，有读书房不对用户的内容提供任何形式的保证：不保证有读书房的服务不会中断。因网络状况、通讯线路、第三方网站或管理部门的要求等任何原因而导致您不能正常使用有读书房，有读书房均不承担任何法律责任。\n"
            ."（2）有读书房是仅为用户提供图书展示与借阅服务的平台，作为内容的发表者，需自行对其发布内容负责，因所发表内容引发的一切纠纷，由该内容的发表者承担全部法律及连带责任，有读书房不承担任何法律及连带责任。\n"
            ."（3）个人或单位如认为有读书房上存在隐私自身合法权益的内容，及时与有读书房取得联系，准备好具有法律效应的证明材料，以便有读书房迅速做出处理。\n\n"
            ."对免责声明的解释、修改及更新权均属于有读书房所有。";
    }

    /**
     * @param mixed $cardId
     * @return string
     * @throws Exceptions\Exception
     */
    public function cardApprove($cardId) {
        Visitor::instance()->checkAuth();

        $userId = intval(Visitor::instance()->getUserId());
        if ($userId !== 34 && $userId !== 35) {
            throw new Exception(Exception::AUTH_FAILED, '没有权限~');
        }

        $cardFlow = MDiscoverFlow::where([
            'type'       => 'card',
            'content_id' => $cardId,
        ])->first();

        if (!$cardFlow) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '卡片不存在~');
        }

        if ($cardFlow->status === MDiscoverFlow::DISCOVER_ITEM_USER_DELETED) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '卡片已被作者删除~');
        }

        $cardFlow->status = MDiscoverFlow::DISCOVER_ITEM_APPROVED;
        $cardFlow->update();

        return 'ok';
    }

    /**
     * @param mixed $cardId
     * @return string
     * @throws Exceptions\Exception
     */
    public function cardDecline($cardId) {
        Visitor::instance()->checkAuth();

        $userId = intval(Visitor::instance()->getUserId());
        if ($userId !== 34 && $userId !== 35) {
            throw new Exception(Exception::AUTH_FAILED, '没有权限~');
        }

        $cardFlow = MDiscoverFlow::where([
            'type'       => 'card',
            'content_id' => $cardId,
        ])->first();

        if (!$cardFlow) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '卡片不存在~');
        }

        if ($cardFlow->status === MDiscoverFlow::DISCOVER_ITEM_USER_DELETED) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '卡片已被作者删除~');
        }

        $cardFlow->status = MDiscoverFlow::DISCOVER_ITEM_DENIED;
        $cardFlow->update();

        return 'ok';
    }
}