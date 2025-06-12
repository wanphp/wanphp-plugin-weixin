<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\SimpleCache\CacheInterface;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\ScopeRepository;

/**
 * Class ScopesApi
 * @title 客户端授权范围
 * @route /admin/client/scopes
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class ScopesApi extends Api
{

  private ScopeRepository $scope;
  private CacheInterface $storage;

  public function __construct(ScopeRepository $scope, Setting $setting)
  {
    $this->scope = $scope;
    $this->storage = $setting->get('AuthCodeStorage');
  }

  /**
   * @inheritDoc
   * @throws InvalidArgumentException
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case  'POST';
        $data = $this->request->getParsedBody();
        $data['scopeRules'] = explode("\n", $data['scopeRules']);
        $data['id'] = $this->scope->insert($data);
        $this->storage->delete('scopes');
        return $this->respondWithData($data, 201);
      case  'PUT';
        $data = $this->request->getParsedBody();
        $data['scopeRules'] = explode("\n", $data['scopeRules']);
        $num = $this->scope->update($data, ['id' => $this->args['id']]);
        $this->storage->delete('scopes');
        return $this->respondWithData(['upNum' => $num], 201);
      case  'DELETE';
        $delNum = $this->scope->delete(['id' => $this->args['id']]);
        $this->storage->delete('scopes');
        return $this->respondWithData(['delNum' => $delNum]);
      case 'GET';
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          try {
            return $this->respondWithData(['data' => $this->scope->select('id,identifier,name,description,scopeRules[JSON]')]);
          } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
          }
        } else {
          $data = [
            'title' => '客户端授权范围管理'
          ];

          return $this->respondView('@weixin/scopes.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}