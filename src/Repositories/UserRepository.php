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

  public function __construct(Database $database, WeChatBase $weChatBase, LoggerInterface $logger)
  {
    parent::__construct($database, self::TABLE_NAME, UserEntity::class);
    $this->weChatBase = $weChatBase;
    $this->logger = $logger;
  }

  public function getUser(int $uid): array
  {
    return $this->db->get(UserInterface::TABLE_NAME . '(u)', [
      '[>]' . PublicInterface::TABLE_NAME . '(p)' => ["u.id" => "id"]
    ],
      ['u.unionid', 'u.nickname', 'u.headimgurl', 'u.name', 'u.tel', 'u.email', 'u.fox', 'u.remark', 'u.address', 'u.status', 'p.tagid_list[JSON]', 'p.openid', 'p.parent_id'],
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
        $msgData['template_id'] = $this->db->get(MsgTemplateInterface::TABLE_NAME, ['template_id'], ['template_id_short' => $msgData['template_id_short'], 'status' => 1]);
        unset($msgData['template_id_short']);
      }
      if (empty($msgData['template_id'])) return ['errCode' => '1', 'msg' => '无模板ID,请先获取模板ID'];
      $openId = $this->db->get(PublicInterface::TABLE_NAME, ['openid'], ['id' => $uidArr, 'subscribe' => 1]);
      if ($openId) {
        if (is_string($openId)) $openId = [$openId];

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
}
