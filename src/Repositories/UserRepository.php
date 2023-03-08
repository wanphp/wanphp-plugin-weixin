<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/29
 * Time: 17:09
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\Weixin\Domain\MsgTemplateInterface;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;
use Wanphp\Plugins\Weixin\Entities\UserEntity;

class UserRepository extends BaseRepository implements UserInterface
{
  private WeChatBase $weChatBase;
  private LoggerInterface $logger;

  public function __construct(Database $database, WeChatBase $weChatBase, LoggerInterface $logger, string $table = '', string $userEntity = '')
  {
    parent::__construct($database, $table ?: self::TABLE_NAME, $userEntity ?: UserEntity::class);
    $this->weChatBase = $weChatBase;
    $this->logger = $logger;
  }

  public function getUser(int $uid): array
  {
    return $this->db->get(UserInterface::TABLE_NAME . '(u)', [
      '[>]' . PublicInterface::TABLE_NAME . '(p)' => ["u.id" => "id"]
    ],
      ['u.unionid', 'u.nickname', 'u.headimgurl', 'u.name', 'u.tel', 'u.remark', 'u.address', 'p.tagid_list[JSON]', 'p.openid', 'p.parent_id'],
      ['u.id' => $uid]
    ) ?: [];
  }

  public function getUsers($uidArr): array
  {
    return $this->db->select(UserInterface::TABLE_NAME . '(u)', [
      '[>]' . PublicInterface::TABLE_NAME . '(p)' => ["u.id" => "id"]
    ],
      ['u.id', 'u.nickname', 'u.headimgurl', 'u.name', 'u.tel', 'u.remark', 'u.address', 'p.openid', 'p.tagid_list[JSON]', 'p.subscribe', 'p.parent_id'],
      ['u.id' => $uidArr]
    ) ?: [];
  }

  public function getUserList($where): array
  {
    return $this->db->select(UserInterface::TABLE_NAME . '(u)', [
      '[>]' . PublicInterface::TABLE_NAME . '(p)' => ["u.id" => "id"]
    ],
      ['u.id', 'u.nickname', 'u.headimgurl', 'u.name', 'u.tel', 'u.remark', 'u.address', 'p.openid', 'p.tagid_list[JSON]', 'p.subscribe', 'p.parent_id'],
      $where
    ) ?: [];
  }

  public function getUserCount($where): int
  {
    return $this->db->count(UserInterface::TABLE_NAME . '(u)', [
      '[>]' . PublicInterface::TABLE_NAME . '(p)' => ["u.id" => "id"]
    ],
      ['u.id'],
      $where
    ) ?: 0;
  }

  /**
   * @param array $data
   * @return array
   * @throws Exception
   */
  public function addUser(array $data): array
  {
    if (!isset($data['unionid']) || count($data['unionid']) != 28) return ['errMsg' => 'unionid不正确'];
    $id = $this->get('id', ['unionid' => $data['unionid']]);
    if ($id) {
      $this->update($data, ['id' => $id]);
    } else {
      $id = $this->insert($data);
    }
    return ['uid' => $id];
  }

  /**
   * @param int $uid
   * @param array $data
   * @return array
   * @throws Exception
   */
  public function updateUser(int $uid, array $data): array
  {
    if (isset($data['unionid'])) return ['errMsg' => '不可以修改unionid'];
    if (isset($data['password']) && !empty($data['password'])) {
      $password = md5(trim($data['password']));
      $data['salt'] = substr(md5(uniqid(rand(), true)), 10, 11);
      $data['password'] = md5(SHA1($data['salt'] . $password));
    }
    if ($uid > 0) $upNum = $this->update($data, ['id' => $uid]);
    return ['upNum' => $upNum ?? 0];
  }

  /**
   * @param string $keyword
   * @param int $page
   * @return array
   * @throws Exception
   */
  #[ArrayShape(['users' => "array", 'total' => "int"])] public function searchUsers(string $keyword, int $page = 0): array
  {
    $where = [];
    $where['OR'] = [
      'name[~]' => $keyword,
      'nickname[~]' => $keyword,
      'tel[~]' => $keyword
    ];
    $page = (max($page, 1) - 1) * 10;
    $where['LIMIT'] = [$page, 10];

    return [
      'users' => $this->select('id,nickname,headimgurl,name,tel', $where),
      'total' => $this->count('id', $where)
    ];
  }

  /**
   * @param array $uidArr
   * @param array $msgData
   * @return array
   */
  public function sendMessage(array $uidArr, array $msgData): array
  {
    if (empty($msgData)) return ['errCode' => '1', 'msg' => '无模板信息内容'];
    //取用户openid
    if (!empty($uidArr)) {
      // 取模板ID
      if (isset($msgData['template_id_short'])) {
        $msgData['template_id'] = $this->db->get(MsgTemplateInterface::TABLE_NAME, 'template_id', ['template_id_short' => $msgData['template_id_short'], 'status' => 1]);
        unset($msgData['template_id_short']);
      }
      if (empty($msgData['template_id'])) return ['errCode' => '1', 'msg' => '无模板ID,请先获取模板ID'];
      $openId = $this->db->select(PublicInterface::TABLE_NAME, 'openid', ['id' => $uidArr, 'subscribe' => 1]);
      if ($openId) {
        $ok = 0;
        foreach ($openId as $openid) {
          $msgData['touser'] = $openid;
          try {
            $this->weChatBase->sendTemplateMessage($msgData);
            $ok++;
          } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
          }
        }
        return ['errCode' => '0', 'ok' => $ok];
      } else {
        return ['errCode' => '1', 'msg' => '用户没有关注公众号'];
      }
    } else {
      return ['errCode' => '1', 'msg' => '未检测到用户ID'];
    }
  }

  /**
   * @throws Exception
   */
  public function membersTagging(string $uid, int $tagId): array
  {
    $openid = $this->db->get(PublicInterface::TABLE_NAME, 'openid', ['id' => $uid]);
    if ($openid) {
      $result = $this->weChatBase->membersTagging($tagId, [$openid]);
      if ($result['errcode'] == 0) {
        $tagid_list = $this->db->get(PublicInterface::TABLE_NAME, 'tagid_list[JSON]', ['openid' => $openid]);
        $tagid_list[] = $tagId;
        $this->db->update(PublicInterface::TABLE_NAME, ['tagid_list' => array_unique($tagid_list)], ['id' => $uid]);
      }
      return $result;
    } else {
      return ['errcode' => 1, 'errmsg' => '未找到用户'];
    }
  }

  /**
   * @throws Exception
   */
  public function membersUnTagging(string $uid, int $tagId): array
  {
    $openid = $this->db->get(PublicInterface::TABLE_NAME, 'openid', ['id' => $uid]);
    if ($openid) {
      $result = $this->weChatBase->membersUnTagging($tagId, [$openid]);
      if ($result['errcode'] == 0) {
        $tagid_list = $this->db->get(PublicInterface::TABLE_NAME, 'tagid_list[JSON]', ['openid' => $openid]);
        $tagid_list = array_values(array_diff($tagid_list, [$tagId]));
        $this->db->update(PublicInterface::TABLE_NAME, ['tagid_list' => $tagid_list], ['openid' => $openid]);
      }
      return $result;
    } else {
      return ['errcode' => 1, 'errmsg' => '未找到用户'];
    }
  }

  public function userLogin(string $account, string $password): int|string
  {
    return '系统是默认使用微信授权用户，无注册用户，需要注册用户，需继承后重写';
  }

  public function oauthRedirect(Request $request, Response $response): Response
  {
    $redirectUri = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . $request->getUri()->getPath();
    $queryParams = $request->getQueryParams();
    $response_type = $queryParams['response_type'] ?? $queryParams['state'] ?? '';
    $url = $this->weChatBase->getOauthRedirect($redirectUri, $response_type);
    return $response->withHeader('Location', $url)->withStatus(301);
  }

  public function getOauthAccessToken(string $code, string $redirect_uri): string
  {
    $accessToken = $this->weChatBase->getOauthAccessToken($code);
    return json_encode($accessToken);
  }

  public function getOauthUserinfo(string $access_token): array
  {
    $accessToken = json_decode($access_token);
    //需要用户授权
    $weUser = $this->weChatBase->getOauthUserinfo($accessToken['access_token'], $accessToken['openid']);
    if (isset($weUser['openid'])) {
      //用户基本数据
      $data = [
        'unionid' => $weUser['unionid'] ?? null,
        'nickname' => $weUser['nickname'],
        'headimgurl' => $weUser['headimgurl'],
        'sex' => $weUser['sex']
      ];
      $userinfo = $this->weChatBase->getUserInfo($weUser['openid']);
      if ($userinfo['subscribe']) {//用户已关注公众号
        $pubData = [
          'subscribe' => $userinfo['subscribe'],
          'tagid_list' => $userinfo['tagid_list'],
          'subscribe_time' => $userinfo['subscribe_time'],
          'subscribe_scene' => $userinfo['subscribe_scene']
        ];
      }
      //检查数据库是否存在用户数据
      $user_id = $this->db->get(PublicInterface::TABLE_NAME, 'id', ['openid' => $accessToken['openid']]);
      if ($user_id) {// 已存在公众号关注信息
        if ($data['unionid']) $uid = $this->get('id', ['unionid' => $data['unionid']]);
        else $uid = $this->get('id', ['id' => $user_id]);
        if ($uid) {
          //更新用户
          if ($uid != $user_id) $data['id'] = $user_id;
          $this->update($data, ['id' => $uid]);
        } else {
          //添加用户
          $data['id'] = $user_id;
          $this->insert($data);
        }
        if (isset($pubData)) $this->db->update(PublicInterface::TABLE_NAME, $pubData, ['id' => $user_id]);
      } else {
        // 不存在公众号关注信息
        //检查用户是否通过小程序等，存储到本地
        if ($data['unionid']) {
          $uid = $this->get('id', ['unionid' => $data['unionid']]);
          if ($uid) {
            $this->update($data, ['id' => $uid]);
            $user_id = $uid;
          } else {
            //添加用户
            $user_id = $this->insert($data);
          }
          //添加公众号数据
          if (isset($pubData)) {
            $pubData['id'] = $user_id;
            $pubData['openid'] = $weUser['openid'];
          } else {
            $pubData = ['id' => $user_id, 'openid' => $weUser['openid']];
          }
          $this->db->insert(PublicInterface::TABLE_NAME, $pubData);
        } else {
          //添加公众号数据
          if (isset($pubData)) $pubData['openid'] = $weUser['openid'];
          else $pubData = ['openid' => $weUser['openid']];
          $data['id'] = $this->db->insert(PublicInterface::TABLE_NAME, $pubData);
          //添加用户
          $user_id = $this->insert($data);
        }
      }
      return $this->get('id,unionid,nickname,headimgurl,name,tel,address,remark', ['id' => $user_id]);
    } else {
      return [];
    }
  }

  public function updateOauthUser(string $access_token, array $data): array
  {
    throw new Exception('微信端未提供用户更新操作接口！');
  }
}
