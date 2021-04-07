<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 14:46
 */

namespace Wanphp\Plugins\Weixin\Application;


use Psr\Log\LoggerInterface;
use Wanphp\Libray\Weixin\Pay;
use Wanphp\Libray\Weixin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class PayNotice extends Api
{
  protected $weChatBase;
  protected $pay;
  protected $user;
  protected $public;
  protected $logger;

  public function __construct(WeChatBase $weChatBase, Pay $pay, UserInterface $user, PublicInterface $public, LoggerInterface $logger)
  {
    $this->weChatBase = $weChatBase;
    $this->pay = $pay;
    $this->user = $user;
    $this->public = $public;
    $this->logger = $logger;
  }

  protected function action(): Response
  {
    if (!$xml = file_get_contents('php://input')) {
      $this->returnCode(false, 'Not found DATA');
    }
    // 将服务器返回的XML数据转化为数组
    $data = $this->pay->fromXml($xml);
    // 记录日志
    $this->logger->info('data', $data);

    //1.退款通知
    if ($data['return_code'] == 'SUCCESS' && isset($data['req_info'])) {
      $req_info = $this->pay->refund_decrypt($data['req_info']);
      $req_info = $this->pay->fromXml($req_info);

      //退款定单

      // 发送短信通知
      $this->returnCode(true, 'OK');
    }
    //2.支付通知
    // 待支付订单详情

    return $this->returnCode(true, '订单不存在');
  }

  protected function returnCode($is_success = true, $msg = null)
  {
    $body = $this->pay->toXml([
      'return_code' => $is_success ? 'SUCCESS' : 'FAIL',
      'return_msg' => $is_success ? 'OK' : $msg,
    ]);
    $this->response->getBody()->write($body);

    return $this->response->withHeader('Content-Type', 'text/xml')->withStatus(200);
  }
}
