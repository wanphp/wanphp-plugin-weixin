<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Slim\RedisCacheFactory;
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
  private CacheItemPoolInterface $storage;

  public function __construct(ScopeRepository $scope, Setting $setting, RedisCacheFactory $redisCacheFactory)
  {
    $this->scope = $scope;
    $config = $setting->get('oauth2Config');
    $this->storage = $redisCacheFactory->create($config['database'] ?? 2, $config['prefix'] ?? 'wp_uc');
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
        $this->storage->deleteItem('scopes');
        return $this->respondWithData($data, 201);
      case  'PUT';
        $data = $this->request->getParsedBody();
        $data['scopeRules'] = explode("\n", $data['scopeRules']);
        $num = $this->scope->update($data, ['id' => $this->args['id']]);
        $this->storage->deleteItem('scopes');
        return $this->respondWithData(['upNum' => $num], 201);
      case  'DELETE';
        $delNum = $this->scope->delete(['id' => $this->args['id']]);
        $this->storage->deleteItem('scopes');
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