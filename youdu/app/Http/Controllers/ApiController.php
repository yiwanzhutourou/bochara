<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Exceptions\NeedRedirectException;
use App\Http\Controllers\Api\Lib\ApiCmd;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MUser;
use App\Utils\ErrorUtils;
use Illuminate\Http\Request;

class ApiController extends Controller {

    // 兼容老 API：/api/Book.get?id=xxx
    public function __invoke(Request $request, string $action = 'index') {
        try {
            $response = $this->handleRequest($request, $action);
            return response()->json($response);
        } catch (Exception $e) {
            return ErrorUtils::apiErrorResponse($e->output(), $e->httpCode());
        } catch (NeedRedirectException $e) {
            return redirect($e->url, $e->status);
        } catch (\Exception $e) {
            return ErrorUtils::apiErrorResponse(
                [
                    'error' => 500,
                    'message' => $e->getMessage(),
                    // 'message' => '服务器发生错误了~',
                    'ext' => '',
                ], 500);
        }
    }

    /**
     * @param Request $request
     * @param         $action
     * @return array
     * @throws Exception
     * @throws NeedRedirectException
     * @throws \Exception
     */
    private function handleRequest(Request $request, $action) {
        $this->initEnv($request);

        $requestData = $request->input();
        $method = $request->method();
        $cmd = new ApiCmd($action, $requestData, $method);
        $cmd->run();
        return [
            'result' => $cmd->result,
        ];
    }

    /**
     * @param Request $request
     * @throws NeedRedirectException
     */
    private function initEnv(Request $request) {
        // 检查平台
        $platform = $request->header('BOCHA-PLATFORM');
        // 目前仅支持微信小程序
//        if (empty($platform) || $platform !== 'wx-mp') {
//            throw new NeedRedirectException('//www.youdushufang.com/');
//        }
        // 验证token
        $token = $request->header('BOCHA-USER-TOKEN');
        if ($token) {
            $user = MUser::where('token', '=', $token)->first();
            Visitor::instance()->setUser($user);
        }
    }
}