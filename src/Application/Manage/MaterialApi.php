<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Exception;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Weixin\WeChatBase;

/**
 * Class AutoReplyApi
 * @title 素材管理
 * @route /admin/weixin/material/list
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class MaterialApi extends \Wanphp\Plugins\Weixin\Application\Api
{
  private WeChatBase $weChatBase;
  private string $filepath;

  /**
   * @param WeChatBase $weChatBase
   * @param Setting $setting
   */
  public function __construct(WeChatBase $weChatBase, Setting $setting)
  {
    $this->weChatBase = $weChatBase;
    $this->filepath = $setting->get('uploadFilePath');
  }

  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case  'POST';
        $type = $this->resolveArg('type');
        $data = $this->getFormData();
        $file = $this->filepath . $data['filePath'];
        if (is_file($file)) {
          try {
            if (isset($data['type']) && $data['type'] == 'temporary') return $this->respondWithData($this->weChatBase->uploadMaterial($type, $file));
            elseif ($type == 'video') return $this->respondWithData($this->weChatBase->addMaterial($type, $file, $data['description']));
            else return $this->respondWithData($this->weChatBase->addMaterial($type, $file));
          } catch (Exception $exception) {
            return $this->respondWithError($exception->getMessage());
          }
        } else {
          return $this->respondWithError('找不到文件');
        }
      case  'DELETE';
        return $this->respondWithData($this->weChatBase->delMaterial($this->resolveArg('media_id')));
      case 'GET';
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $params = $this->request->getQueryParams();

          $material = $this->weChatBase->batchGetMaterial(["type" => $this->resolveArg('type'), "offset" => $params['start'] ?? 0, "count" => $params['length'] ?? 10]);
          $data = [
            "draw" => $params['draw'],
            "recordsTotal" => $material['total_count'] ?? 0,
            "recordsFiltered" => $material['total_count'] ?? 0,
            'data' => array_chunk($material['item'], 5),
          ];

          return $this->respondWithData($data);
        } else {
          $data = [
            'title' => '永久素材管理'
          ];

          return $this->respondView('@weixin/material.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }

  /**
   * 对话框内显示
   * @throws Exception
   */
  public function dialog(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    return $this->respondView('@weixin/materialDialog.html');
  }

  /**
   * 图片、音频
   * @throws Exception
   */
  public function media(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $resp = $this->weChatBase->getMaterial($this->resolveArg('media_id'), false);
    return $this->response
      ->withHeader('Content-Type', $resp['content_type'])
      ->withHeader('Content-Disposition', $resp['content_disposition'])
      ->withBody(Utils::streamFor($resp['body']));
  }

  /**
   * 视频
   * @throws Exception
   */
  public function video(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $resp = $this->weChatBase->getMaterial($this->resolveArg('media_id'), false);
    if ($resp['down_url']) {
      $client = new \GuzzleHttp\Client();
      $response = $client->request('GET', $resp['down_url']);
      return $this->response
        ->withHeader('Content-Type', $response->getHeaderLine('Content-Type'))
        ->withHeader('Content-Disposition', $response->getHeaderLine('Content-disposition'))
        ->withBody(Utils::streamFor($response->getBody()->getContents()));
    }
    return $this->respondWithData($resp);
  }
}
