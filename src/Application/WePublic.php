<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 14:08
 */

namespace Wanphp\Plugins\Weixin\Application;


use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Weixin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Domain\AutoReplyInterface;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

abstract class WePublic extends Api
{
  protected WeChatBase $weChatBase;
  protected UserInterface $user;
  protected PublicInterface $public;
  protected AutoReplyInterface $autoReply;
  protected Key $encryptionKey;

  /**
   * @throws EnvironmentIsBrokenException
   * @throws BadFormatException
   */
  public function __construct(WeChatBase $weChatBase, Setting $setting, UserInterface $user, PublicInterface $public, AutoReplyInterface $autoReply)
  {
    $this->weChatBase = $weChatBase;
    $this->user = $user;
    $this->public = $public;
    $this->autoReply = $autoReply;
    $this->encryptionKey = Key::loadFromAsciiSafeString($setting->get('oauth2Config')['encryptionKey']);
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Post(
   *  path="/weixin",
   *  tags={"Public"},
   *  summary="微信服务地址，使用时按实际情况自行继承重写此方法",
   *  operationId="weixinMsgEvent",
   *  @OA\RequestBody(
   *    description="接收微信消息、事件，当用户向公众账号发消息时或与公众号产生交互时，微信服务器将POST消息的XML数据包当前URL上进行处理。",
   *    @OA\XmlContent(type="string")
   *  ),
   *  @OA\Response(
   *    response=200,
   *    description="返回结果",
   *    @OA\XmlContent(type="string")
   *  )
   * )
   */
  protected function action(): Response
  {
    if ($this->weChatBase->valid($this->request->getQueryParams()) === true) {
      $openid = $this->weChatBase->getRev()->getRevFrom();//获取每个微信用户的openid
      $time = $this->weChatBase->getRev()->getRevCtime();//获取消息发送时间
      $type = $this->weChatBase->getRev()->getRevType();//获取消息类型

      $body = '';
      switch ($type) {
        case 'event':
          // 处理事件推送
          $eventArr = $this->weChatBase->getRev()->getRevEvent();
          $event = $eventArr['event'] ?? '';//获得事件类型
          switch ($event) {
            case 'subscribe':
              $this->updateUser();
              //关注自动回复文本信息
              $body = $this->subscribe();
              break;
            case 'unsubscribe':
              $this->public->update([
                'subscribe' => 0,
                'unsubscribe_time' => $time,
                'lastop_time' => 0],
                ['openid' => $openid]);
              break;
            case 'SCAN':
              // 扫码
              if ($eventArr['key']) {
                $body = $this->userScan($eventArr['key'], $openid);
              }
              break;
            default:
              if ($event == 'CLICK' && !$this->weChatBase->webAuthorization && $eventArr['key'] == '授权') {
                $body = $this->getAuthorizationLink();
              } else {
                $body = $this->clickevent();
              }
          }
          break;
        case 'text':
          $this->endMsgTime($openid);
          if (!$this->weChatBase->webAuthorization && $this->weChatBase->getRev()->getRevContent() == '授权') {
            $body = $this->getAuthorizationLink();
          } else {
            // 处理关键词回复
            $body = $this->text();
          }
          break;
        case 'image':
          $this->endMsgTime($openid);
          // 接收图片
          $body = $this->image();
          break;
        case 'voice':
          $this->endMsgTime($openid);
          // 接收语音
          $body = $this->voice();
          break;
        case 'video':
          $this->endMsgTime($openid);
          // 接收视频
          $body = $this->video();
          break;
        case 'shortvideo':
          $this->endMsgTime($openid);
          // 接收短视频
          $body = $this->shortvideo();
          break;
        default:
          $body = $this->weChatBase->Message('text', ['Content' => '收到']);
      }
      $this->response->getBody()->write($body);
      return $this->response->withHeader('Content-Type', 'text/xml')->withStatus(200);
    } else {
      $this->response->getBody()->write($this->weChatBase->valid($this->request->getQueryParams()));
      return $this->response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }
  }

  /**
   * @return int
   * @throws Exception
   */
  protected function updateUser(): int
  {
    $openid = $this->weChatBase->getRev()->getRevFrom();//获取每个微信用户的openid
    $time = time();
    $info = $this->public->get('id,lastop_time,subscribe', ['openid' => $openid]);
    if (isset($info['lastop_time']) && $info['lastop_time'] > ($time - 172800) && $info['subscribe'] == 1) return $info['id']; // 两天内已更新过用户信息

    //保存用户信息
    try {
      $userinfo = $this->weChatBase->getUserInfo($openid);
      //本地存储用户
      $data = [
        'subscribe' => 1,
        'tagid_list' => $userinfo['tagid_list'],
        'subscribe_time' => $userinfo['subscribe_time'],
        'subscribe_scene' => $userinfo['subscribe_scene'],
        'lastop_time' => $time
      ];
      if (isset($info['id'])) {//二次关注
        $user_id = $info['id'];
        //更新公众号信息
        $data['unsubscribe_time'] = 0;
        $this->public->update($data, ['id' => $user_id]);
      } else {
        $data['openid'] = $openid;
        $data['parent_id'] = $userinfo['qr_scene'];
        //检查用户是否通过小程序等，存储到本地
        if (!empty($userinfo['unionid'])) {
          $user_id = $this->user->get('id', ['unionid' => $userinfo['unionid']]);
          $data['id'] = $user_id ?: $this->user->insert(['unionid' => $userinfo['unionid']]);
        }
        //添加公众号信息
        $user_id = $this->public->insert($data);
        if (empty($userinfo['unionid'])) $this->user->insert(['id' => $user_id]);
      }
    } catch (Exception) {
      if (isset($info['id'])) {//二次关注
        $user_id = $info['id'];
        //更新公众号信息
        $this->public->update(['subscribe' => 1, 'unsubscribe_time' => 0], ['id' => $user_id]);
      } else {
        //本地存储用户
        $data = [
          'openid' => $openid,
          'subscribe' => 1,
          'subscribe_time' => $time,
          'lastop_time' => $time
        ];
        $id = $this->public->insert($data);
        $user_id = $this->user->get('id', ['id' => $id]);
        if (!$user_id) $user_id = $this->user->insert(['id' => $id]);
      }
    }
    return $user_id;
  }

  /**
   * 接收文本消息
   * @return string
   * @throws Exception
   */
  protected function text(): string
  {
    $text = $this->weChatBase->getRev()->getRevContent();//获取消息内容
    $msgData = $this->autoReply->get('msgContent[JSON],replyType', ['key' => $text]);
    if ($msgData) {
      if ($msgData['replyType'] == 'music' && !isset($msgData['msgContent']['Music']['HQMusicUrl'])) {
        $msgData['msgContent']['Music']['HQMusicUrl'] = $msgData['msgContent']['Music']['MusicUrl'];
      }
      return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    }
    return '';
  }

  /**
   * 接收图片
   * @return string
   * @throws Exception
   */
  protected function image(): string
  {
    $msgData = $this->autoReply->get('msgContent[JSON],replyType', ['key' => 'image']);
    if ($msgData) return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    return '';
  }

  /**
   * 接收语音
   * @return string
   * @throws Exception
   */
  protected function voice(): string
  {
    $msgData = $this->autoReply->get('msgContent[JSON],replyType', ['key' => 'voice']);
    if ($msgData) return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    return '';
  }

  /**
   * 接收视频
   * @return string
   * @throws Exception
   */
  protected function video(): string
  {
    $msgData = $this->autoReply->get('msgContent[JSON],replyType', ['key' => 'video']);
    if ($msgData) {
      // 删除封面
      unset($msgData['msgContent']['Cover']);
      return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    }
    return '';
  }

  /**
   * 接收短视频
   * @return string
   * @throws Exception
   */
  protected function shortVideo(): string
  {
    $msgData = $this->autoReply->get('msgContent[JSON],replyType', ['key' => 'shortvideo']);
    if ($msgData) return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    return '';
  }

  /**
   * 用户关注自动回复
   * @return string
   * @throws Exception
   */
  protected function subscribe(): string
  {
    $msgData = $this->autoReply->get('msgContent[JSON],replyType', ['key' => 'subscribe']);
    if ($msgData) return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    return '';
  }

  /**
   * 用户点击自定义菜单
   * @return string
   * @throws Exception
   */
  protected function clickEvent(): string
  {
    $event = $this->weChatBase->getRevEvent();
    if (!$event) return '';
    $msgData = $this->autoReply->get('msgContent[JSON],replyType', ['key' => $event['key']]);
    if ($msgData) return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    return '';
  }

  /**
   * 记录用户最后发送信息的时间，用断断是否可发服消息
   * @param $openid
   * @return void
   * @throws Exception
   */
  protected function endMsgTime($openid): void
  {
    $this->public->update(['lastop_time' => time()], ['openid' => $openid]);
  }

  /**
   * 扫码执行操作
   * @param string $scan_key
   * @param string $openid
   * @return string
   * @throws Exception
   */
  protected function userScan(string $scan_key, string $openid): string
  {
    $uid = $this->public->get('id', ['openid' => $openid]);
    $qrRes = explode('_', $scan_key);
    $body = '';
    switch ($qrRes['0']) {
      case 'shareUid':
        // 用户邀请用户
        break;
      default:
    }
    return $body;
  }

  /**
   * 没有网页授权的公众号，通过自定义授权链接授权
   * @return array|string
   * @throws Exception
   */
  private function getAuthorizationLink(): string|array
  {
    $user_id = $this->updateUser();
    if ($user_id) {
      $code = Crypto::encrypt($user_id . '', $this->encryptionKey);
      $body = $this->weChatBase->Message('text', ['Content' => '<a href="' . $this->httpHost() . '/auth/authorize?code=' . $code . '&state=code">点击确认授权</a>']);
    }
    return $body ?? '';
  }
}
