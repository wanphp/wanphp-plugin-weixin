<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 14:08
 */

namespace Wanphp\Plugins\Weixin\Application;


use Exception;
use Wanphp\Libray\Weixin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

abstract class WePublic extends Api
{
  protected WeChatBase $weChatBase;
  protected UserInterface $user;
  protected PublicInterface $public;

  public function __construct(WeChatBase $weChatBase, UserInterface $user, PublicInterface $public)
  {
    $this->weChatBase = $weChatBase;
    $this->user = $user;
    $this->public = $public;
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
                'integral' => 0,
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
              $body = $this->clickevent($this->weChatBase->getRevData());
          }
          break;
        case 'text':
          $this->endMsgTime($openid);
          // 处理关键词回复
          $body = $this->text();
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
          $body = $this->weChatBase->text('收到')->reply();
      }
      $this->response->getBody()->write($body);
      return $this->response->withHeader('Content-Type', 'text/xml')->withStatus(200);
    } else {
      $this->response->getBody()->write($this->weChatBase->valid($this->request->getQueryParams()));
      return $this->response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }
  }

  /**
   * @return false|void
   * @throws Exception
   */
  protected function updateUser()
  {
    $openid = $this->weChatBase->getRev()->getRevFrom();//获取每个微信用户的openid
    $time = time();
    $info = $this->public->get('id,lastop_time', ['openid' => $openid]);
    if (isset($info['lastop_time']) && $info['lastop_time'] > ($time - 172800)) return false; // 两天内已更新过用户信息

    //保存用户信息
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
      //更新公众号信息
      $data['unsubscribe_time'] = 0;
      $this->public->update($data, ['id' => $info['id']]);
    } else {
      $data['openid'] = $openid;
      $data['parent_id'] = $userinfo['qr_scene'];
      //检查用户是否通过小程序等，存储到本地
      if (isset($userinfo['unionid']) && !empty($userinfo['unionid'])) {
        $user_id = $this->user->get('id', ['unionid' => $userinfo['unionid']]);
        if ($user_id) {
          $data['id'] = $user_id;
        } else {
          $data['id'] = $this->user->insert(['unionid' => $userinfo['unionid']]);
        }
      }
      //添加公众号信息
      $this->public->insert($data);
    }
  }

  /**
   * 接收文本消息
   * @return string
   * @throws Exception
   */
  protected function text(): string
  {
    $text = $this->weChatBase->getRev()->getRevContent();//获取消息内容
    return $this->weChatBase->text($text)->reply();
  }

  /**
   * 接收图片
   * @return string
   * @throws Exception
   */
  protected function image(): string
  {
    $image = $this->weChatBase->getRevPic();
    return $this->weChatBase->text(print_r($image, true))->reply();
  }

  /**
   * 接收语音
   * @return string
   * @throws Exception
   */
  protected function voice(): string
  {
    $voice = $this->weChatBase->getRevVoice();
    return $this->weChatBase->text(print_r($voice, true))->reply();
  }

  /**
   * 接收视频
   * @return string
   * @throws Exception
   */
  protected function video(): string
  {
    $video = $this->weChatBase->getRevVideo();
    return $this->weChatBase->text(print_r($video, true))->reply();
  }

  /**
   * 接收短视频
   * @return string
   * @throws Exception
   */
  protected function shortVideo(): string
  {
    $video = $this->weChatBase->getRevVideo();
    return $this->weChatBase->text(print_r($video, true))->reply();
  }

  /**
   * 用户关注自动回复
   * @return string
   * @throws Exception
   */
  protected function subscribe(): string
  {
    return $this->weChatBase->text('感谢关注！')->reply();
  }

  /**
   * 用户点击自定义菜单
   * @param $data
   * @return string
   * @throws Exception
   */
  protected function clickEvent($data): string
  {
    return $this->weChatBase->text(print_r($data, true))->reply();
  }

  /**
   * 记录用户最后发送信息的时间，用断断是否可发服消息
   * @param $openid
   * @return void
   * @throws Exception
   */
  private function endMsgTime($openid)
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
  private function userScan(string $scan_key, string $openid): string
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
}
