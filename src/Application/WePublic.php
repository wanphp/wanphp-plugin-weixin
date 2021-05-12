<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 14:08
 */

namespace Wanphp\Plugins\Weixin\Application;


use Wanphp\Libray\Weixin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class WePublic extends Api
{
  protected $weChatBase;
  protected $user;
  protected $public;
  protected $logger;

  public function __construct(WeChatBase $weChatBase, UserInterface $user, PublicInterface $public, LoggerInterface $logger)
  {
    $this->weChatBase = $weChatBase;
    $this->user = $user;
    $this->public = $public;
    $this->logger = $logger;
  }

  protected function action(): Response
  {
    if ($this->weChatBase->valid() === true) {
      $openid = $this->weChatBase->getRev()->getRevFrom();//获取每个微信用户的openid
      $time = $this->weChatBase->getRev()->getRevCtime();//获取消息发送时间
      //$msgid = $this->weChatBase->getRev()->getRevID();//获取消息ID
      $type = $this->weChatBase->getRev()->getRevType();//获取消息类型
      //不是事件消息，更新最后操作时间
      if ($type != 'event') $this->public->update(['lastop_time' => $time], ['openid' => $openid]);
      $body = '';
      switch ($type) {
        case 'event':
          // 处理事件推送
          $eventArr = $this->weChatBase->getRev()->getRevEvent();
          $event = $eventArr['event'] ?? '';//获得事件类型
          if (in_array($event, array('CLICK', 'SCAN', 'scancode_push', 'scancode_waitmsg', 'merchant_order'))) {
            $this->public->update(['lastop_time' => $time], ['openid' => $openid]);
          }

          switch ($event) {
            case 'subscribe':
              $this->updateUser();
              //关注自动回复文本信息
              $body = $this->subscribe();
              break;
            case 'unsubscribe':
              $data = array();
              $data['subscribe'] = 0;
              $data['unsubscribe_time'] = $time;
              $data['integral'] = 0;
              $data['lastop_time'] = 0;
              $this->public->update($data, ['openid' => $openid]);
              break;
            default:
              $body = $this->clickevent($this->weChatBase->getRevData());
          }
          break;
        case 'text':
          $this->updateUser();
          // 处理关键词回复
          $body = $this->text();
          break;
        case 'image':
          $this->updateUser();
          // 接收图片
          $body = $this->image();
          break;
        case 'voice':
          $this->updateUser();
          // 接收语音
          $body = $this->voice();
          break;
        case 'video':
          $this->updateUser();
          // 接收视频
          $body = $this->video();
          break;
        case 'shortvideo':
          $this->updateUser();
          // 接收短视频
          $body = $this->shortvideo();
          break;
        default:
          $body = $this->weChatBase->text('收到')->reply();
      }
      $this->response->getBody()->write($body);
      return $this->response->withHeader('Content-Type', 'text/xml')->withStatus(200);
    } else {
      $this->response->getBody()->write($this->weChatBase->valid());
      return $this->response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }
  }

  /**
   * @throws \Exception
   */
  protected function updateUser()
  {
    $openid = $this->weChatBase->getRev()->getRevFrom();//获取每个微信用户的openid
    $time = $this->weChatBase->getRev()->getRevCtime();
    $info = $this->public->get('id,lastop_time', ['openid' => $openid]);
    if (isset($info['lastop_time']) && $info['lastop_time'] > ($time - 172800)) return false; // 两天内已更新过用户信息

    //保存用户信息
    $userinfo = $this->weChatBase->getUserInfo($openid);
    if (!is_array($userinfo)) $userinfo = [];
    if (isset($userinfo['groupid'])) unset($userinfo['groupid']);
    //本地存储用户
    if (isset($info['id'])) {//二次关注
      //更新用户信息
      $this->user->update([
        'nickname' => $userinfo['nickname'],
        'headimgurl' => $userinfo['headimgurl'],
        'sex' => $userinfo['sex']
      ], ['id' => $info['id']]);
      //更新公众号信息
      $this->public->update([
        'subscribe' => 1,
        'tagid_list[JSON]' => $userinfo['tagid_list'],
        'subscribe_time' => $userinfo['subscribe_time'],
        'unsubscribe_time' => 0,
        'subscribe_scene' => $userinfo['subscribe_scene'],
        'lastop_time' => $time
      ], ['id' => $info['id']]);
    } else {
      //检查用户是否通过小程序等，存储到本地
      if (isset($userinfo['unionid'])) {
        $user_id = $this->user->get('id', ['unionid' => $userinfo['unionid']]);
        if ($user_id > 0) {
          //更新用户信息
          $this->user->update([
            'nickname' => $userinfo['nickname'],
            'headimgurl' => $userinfo['headimgurl'],
            'sex' => $userinfo['sex']
          ], ['id' => $info['id']]);
        } else {
          $user_id = $this->user->insert([
            'unionid' => $userinfo['unionid'],
            'nickname' => $userinfo['nickname'],
            'headimgurl' => $userinfo['headimgurl'],
            'sex' => $userinfo['sex']
          ]);
        }
      } else {
        $user_id = $this->user->insert([
          'nickname' => $userinfo['nickname'],
          'headimgurl' => $userinfo['headimgurl'],
          'sex' => $userinfo['sex']
        ]);
      }
      //添加公众号信息
      $this->public->insert([
        'id' => $user_id,
        'openid' => $openid,
        'parent_id' => $userinfo['qr_scene'],
        'subscribe' => 1,
        'tagid_list[JSON]' => $userinfo['tagid_list'],
        'subscribe_time' => $userinfo['subscribe_time'],
        'subscribe_scene' => $userinfo['subscribe_scene'],
        'lastop_time' => $time
      ]);
    }
  }

  /**
   * 接收文本消息
   * @return string
   * @throws \Exception
   */
  protected function text()
  {
    $text = $this->weChatBase->getRev()->getRevContent();//获取消息内容
    return $this->weChatBase->text($text)->reply();
  }

  /**
   * 接收图片
   * @return string
   * @throws \Exception
   */
  protected function image()
  {
    $image = $this->weChatBase->getRevPic();
    return $this->weChatBase->text(print_r($image, true))->reply();
  }

  /**
   * 接收语音
   * @return string
   * @throws \Exception
   */
  protected function voice()
  {
    $voice = $this->weChatBase->getRevVoice();
    return $this->weChatBase->text(print_r($voice, true))->reply();
  }

  /**
   * 接收视频
   * @return string
   * @throws \Exception
   */
  protected function video()
  {
    $video = $this->weChatBase->getRevVideo();
    return $this->weChatBase->text(print_r($video, true))->reply();
  }

  /**
   * 接收短视频
   * @return string
   * @throws \Exception
   */
  protected function shortvideo()
  {
    $video = $this->weChatBase->getRevVideo();
    return $this->weChatBase->text(print_r($video, true))->reply();
  }

  /**
   * 用户关注自动回复
   * @return string
   * @throws \Exception
   */
  protected function subscribe()
  {
    return $this->weChatBase->text('感谢关注！')->reply();
  }

  /**
   * 用户点击自定义菜单
   * @param $data
   * @return string
   * @throws \Exception
   */
  protected function clickevent($data)
  {
    return $this->weChatBase->text(print_r($data, true))->reply();
  }

}
