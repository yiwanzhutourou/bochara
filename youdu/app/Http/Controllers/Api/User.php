<?php

namespace App\Http\Controllers\Api;

use App\Formatters\UserFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MBorrowHistory;
use App\Models\MDiscoverFlow;
use App\Models\MUser;
use App\Models\MUserBook;
use App\Repositories\UserRepository;
use App\Utils\JsonUtils;

class User extends ApiBase {

    // TODO 要验一下
    /**
     * @param mixed $code
     * @param mixed $nickname
     * @param mixed $avatar
     * @return array
     * @throws Exception
     */
    public function login($code, $nickname, $avatar) {
        $url = "https://api.weixin.qq.com/sns/jscode2session?" . http_build_query([
                'appid'      => env('WX_APP_ID'),
                'secret'     => env('WX_SECRET'),
                'js_code'    => $code,
                'grant_type' => 'authorization_code'
            ]);

        $response = json_decode(file_get_contents($url));
        if (!empty($response->errcode)) {
            throw new Exception(Exception::WEIXIN_AUTH_FAILED, '微信验证出错:'.$response->errmsg);
        }

        $openId = $response->openid;
        $session = $response->session_key;

        if (!empty($openId) && !empty($session)) {
            $user = UserRepository::findByWxOpenId($openId);
            if ($user && !empty($user->token)) {
                $token = $user->token;
                $user->wechat_open_id = $openId;
                $user->wechat_session = $session;
                // 假的,暂时token不超时
                $user->create_time = strtotime('now');
                $user->expire_time = strtotime('now + 30 days');
                if (empty($user->nickname)) {
                    $user->nickname = $nickname;
                }
                if (empty($user->avatar)) {
                    $user->avatar = $avatar;
                }
                $user->update();
            } else {
                $token = 'bocha' . uniqid('', true);
                $user = new MUser();
                $user->token = $token;
                $user->wechat_open_id = $openId;
                $user->wechat_session = $session;
                // 假的,暂时token不超时
                $user->create_time = strtotime('now');
                $user->expire_time = strtotime('now + 30 days');
                $user->nickname = $nickname;
                $user->avatar = $avatar;
                $user->contact = "";
                $user->mobile = "";
                $user->save();
            }
            return [
                'token'     => $token,
                'hasMobile' => $user && !empty($user->mobile)
            ];
        }

        throw new Exception(Exception::WEIXIN_AUTH_FAILED, '无法获取openid');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSettingsData() {
        Visitor::instance()->checkAuth();
        $user = Visitor::instance()->getUser();
        $contactJson = $user->contact;
        $contact = json_decode($contactJson);
        $mobile = $user->mobile;

        $addressList = [];
        $addresses = $user->addresses()->get();
        foreach ($addresses as $address) {
            if ($address) {
                $addressList[] = json_decode($address->city);
            }
        }

        return [
            'contact'     => $contact,
            'mobileTail'  => substr($mobile, strlen($mobile) - 4, strlen($mobile)),
            'address'     => $addressList,
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMinePageData() {
        Visitor::instance()->checkAuth();
        $user = Visitor::instance()->getUser();
        return [
            'nickname'       => $user->nickname,
            'avatar'         => $user->avatar,
            'bookCount'      => $user->userBooks()->count(),
            'cardCount'      => $user->cards()->count(),
            'followerCount'  => $user->followerCount(),
            'followingCount' => $user->followingCount(),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUserContact() {
        Visitor::instance()->checkAuth();
        return UserFormatter::contact(Visitor::instance()->getUser());
    }

    /**
     * @param $requestId
     * @return array
     * @throws Exception
     */
    public function getUserContactByRequest($requestId) {
        Visitor::instance()->checkAuth();

        $history = MBorrowHistory::find($requestId);
        if ($history) {
            if ($history->from_user !== Visitor::instance()->getUserId()) {
                throw new Exception(Exception::BAD_REQUEST, '无法查看~');
            }
            if ($history->status === MBorrowHistory::BORROW_STATUS_AGREED) {
                $bookOwner = MUser::find($history->to_user);
                if ($bookOwner) {
                    return UserFormatter::contact($bookOwner);
                }
                throw new Exception(Exception::RESOURCE_NOT_FOUND, '用户不存在');
            }
            throw new Exception(Exception::BAD_REQUEST, '书房主人还未同意请求~');
        }
        throw new Exception(Exception::RESOURCE_NOT_FOUND, '借书请求不存在');
    }

    /**
     * @param $name
     * @param $contact
     * @return array
     * @throws Exception
     */
    public function setUserContact($name, $contact) {
        Visitor::instance()->checkAuth();
        $user = Visitor::instance()->getUser();
        $userContact = [
            'name'    => $name,
            'contact' => $contact
        ];
        $user->contact = JsonUtils::json_stringify($userContact);
        $user->update();
        return $userContact;
    }

    /**
     * @param $isbn
     * @return mixed
     * @throws Exception
     */
    public function removeBook($isbn) {
        Visitor::instance()->checkAuth();
        $user = Visitor::instance()->getUser();

        try {
            $result = MUserBook::where([
                'user_id' => $user->id,
                'isbn' => $isbn,
            ])->delete();
        } catch (\Exception $e) {
            $result = false;
        }
        if ($result) {
            // 图书从发现流中删除
            MDiscoverFlow::where([
                'user_id' => $user->id,
                'content_id' => $isbn,
            ])->update(['status' => MDiscoverFlow::DISCOVER_ITEM_USER_DELETED]);
            return $isbn;
        } else {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '无法删除未添加的图书~');
        }
    }
}