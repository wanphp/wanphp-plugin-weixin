<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/28
 * Time: 15:48
 */

namespace Wanphp\Plugins\Weixin\Application\Manage;


use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;
use Wanphp\Libray\Slim\HttpTrait;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

/**
 * Class UserApi
 * @title 用户管理
 * @route /admin/weixin/user
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class UserApi extends Api
{
  use HttpTrait;

  private UserInterface $user;
  private PublicInterface $public;
  private WeChatBase $weChatBase;
  private string $prefix;
  private string $appid;
  private CacheInterface $cache;

  public function __construct(UserInterface $user, PublicInterface $public, Setting $setting, WeChatBase $weChatBase, CacheInterface $cache)
  {
    $this->user = $user;
    $this->public = $public;
    $this->weChatBase = $weChatBase;
    $this->prefix = $setting->get('database')['prefix'];
    $this->appid = $setting->get('wechat.base')['appid'] ?? '';
    $this->cache = $cache;
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Patch(
   *  path="/admin/weixin/user/{id}",
   *  tags={"User"},
   *  summary="更新用户，管理员操作",
   *  operationId="editUser",
   *  security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="用户ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
   *  @OA\RequestBody(
   *    description="指定更新用户数据",
   *    required=true,
   *    @OA\MediaType(
   *      mediaType="application/json",
   *      @OA\Schema(ref="#/components/schemas/UserEntity"),
   *      example={"name": "", "tel": null, "address": "", "integral": "0", "cash_back": "0.00", "money": "0.00"}
   *    )
   *  ),
   *  @OA\Response(
   *    response="201",
   *    description="用户更新成功",
   *  @OA\JsonContent(
   *     allOf={
   *      @OA\Schema(ref="#/components/schemas/Success"),
   *      @OA\Schema(@OA\Property(property="upNum",type="integer",description="更新数量"))
   *     }
   *   )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Get(
   *   path="/admin/weixin/user/{id}",
   *   tags={"User"},
   *   summary="查看用户信息，后台管理员查看",
   *   operationId="getUser",
   *   security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="用户ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
   *   @OA\Response(
   *    response="200",
   *    description="用户信息",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(ref="#/components/schemas/UserEntity")
   *      }
   *    )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'PUT':
        $data = $this->request->getParsedBody();
        if (empty($data) || empty($data['openid'])) return $this->respondWithError('用户还未关注公众号或未授权个人信息');
        $userinfo = $this->weChatBase->getUserInfo($data['openid']);
        if ($userinfo['subscribe']) {//用户已关注公众号
          $pubData = [
            'subscribe' => $userinfo['subscribe'],
            'tagid_list' => $userinfo['tagid_list'],
            'subscribe_time' => $userinfo['subscribe_time'],
            'subscribe_scene' => $userinfo['subscribe_scene']
          ];
          $this->public->update($pubData, ['id' => $this->args['id']]);
          return $this->respondWithData($pubData, 201);
        } else {
          return $this->respondWithError('用户还未关注公众号');
        }
      case 'PATCH':
        $data = $this->request->getParsedBody();
        if (empty($data)) return $this->respondWithError('无用户数据');
        $num = $this->user->update($data, ['id' => $this->args['id']]);
        return $this->respondWithData(['upNum' => $num], 201);
      case 'GET':
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $params = $this->request->getQueryParams();

          $userCount = $this->user->count('id');
          if ($userCount == 0) {
            // 公众号刚加入，取已关注粉丝
            $next_openid = '';
            do {
              $users = $this->weChatBase->getUserList($next_openid);
              $userData = [];
              foreach ($users['data']['openid'] as $openid) {
                $userData[] = ['openid' => $openid, 'subscribe' => 1];
              }
              $this->public->insert($userData);
              $userData = [];
              foreach ($this->public->select('id') as $id) {
                $userData[] = ['id' => $id];
              }
              $this->user->insert($userData);
              $next_openid = $users['next_openid'];
            } while ($users['count'] === 10000);
          }
          $data = $this->user->getUserList($params);
          $users = $data['users'];
          $recordsFiltered = $data['total'];

          // 取用户信息
          $official_account_user = [];
          if ($this->cache->has('forever_' . $this->appid . '_official_account_cookie')) {
            $cookie = $this->cache->get('forever_' . $this->appid . '_official_account_cookie');
            $endIndex = count($users) - 1;
            $next_openid = '';
            $begin_create_time = time();
            if (!empty($params['start']) && $endIndex > 0) {
              $next_openid = $users[$endIndex]['openid'];
              $begin_create_time = $users[$endIndex]['subscribe_time'];
            }
            $res = $this->request(new Client(), 'GET', 'https://mp.weixin.qq.com/cgi-bin/user_tag?action=get_user_list&groupid=-2&begin_openid=' . $next_openid . '&begin_create_time=' . $begin_create_time . '&limit=20&offset=0&backfoward=1&token=' . $cookie['token'] . '&lang=zh_CN&f=json&ajax=1&random=' . (mt_rand() / mt_getrandmax()), [
              'headers' => [
                'cookie' => trim($cookie['cookies'])
              ]
            ]);
            if (!empty($res['user_list']['user_info_list'])) foreach ($res['user_list']['user_info_list'] as $user) {
              $official_account_user[$user['user_openid']] = $user;
              $id = $this->public->get('id', ['openid' => $user['user_openid']]);
              if ($id) {
                $this->user->update(
                  ['nickname' => $user['user_name'], 'headimgurl' => parse_url($user['user_head_img'], PHP_URL_PATH), 'remark' => $user['user_remark']],
                  ['id' => $id]
                );
                $this->public->update(['subscribe_time' => $user['user_create_time']], ['id' => $id]);
              }
            }
          }
          $openidList = [];
          foreach ($users as &$user) {
            if ($user['subscribe'] == 1 && $user['subscribe_time'] == 0) $openidList[] = ['openid' => $user['openid']];
            if (isset($official_account_user[$user['openid']])) {
              $user['nickname'] = $official_account_user[$user['openid']]['user_name'];
              $user['headimgurl'] = $official_account_user[$user['openid']]['user_head_img'];
            }
          }
          if (count($openidList) > 0) {
            try {
              $userList = $this->weChatBase->getUserListInfo($openidList);
              if (!empty($userList['user_info_list'])) foreach ($userList['user_info_list'] as $userinfo) {
                $index = array_search($userinfo['openid'], array_column($users, 'openid'));
                if ($userinfo['subscribe']) {
                  $upData = [
                    'tagid_list' => $userinfo['tagid_list'],
                    'subscribe_time' => $userinfo['subscribe_time'],
                    'subscribe_scene' => $userinfo['subscribe_scene'],
                    'qr_scene' => $userinfo['qr_scene'] . ($userinfo['qr_scene_str'] ? "({$userinfo['qr_scene_str']})" : ''),
                    'remark' => $userinfo['remark']
                  ];
                } else {
                  $upData = ['subscribe' => 0];
                }
                $users[$index] = array_merge($users[$index], $upData);
                $this->user->update(
                  $upData,
                  ['openid' => $userinfo['openid']]
                );
              }
            } catch (Exception) {
              // 无获取用户信息权限
            }
          }

          $data = [
            "draw" => $params['draw'],
            "recordsTotal" => $userCount,
            "recordsFiltered" => $recordsFiltered,
            'data' => $users
          ];

          return $this->respondWithData($data);
        } else {
          $data = [
            'title' => '微信用户管理',
            'tags' => [],
            'userTags' => '{}'
          ];
          try {
            $userTags = $this->weChatBase->getTags();
            $data['tags'] = $userTags['tags'];
            $userTags = array_column($userTags['tags'], 'name', 'id');
            $data['userTags'] = json_encode($userTags);
          } catch (Exception) {
            // 无获取用户标签权限
          }
          return $this->respondView('@weixin/user-list.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }

  /**
   * 设置公众号Cookie
   * @param Request $request
   * @param Response $response
   * @param array $args
   * @return Response
   * @throws Exception
   */
  public function setCookie(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;
    $data = $this->getFormData();
    if (!empty($this->appid)) {
      // 设置缓存
      $this->cache->set('forever_' . $this->appid . '_official_account_cookie', $data, 316800);// 88小时
      return $this->respondWithData(['code' => '0', 'msg' => '已授权成功！']);
    } else {
      return $this->respondWithError('Error!');
    }

  }
}
