<?php

namespace Wanphp\Plugins\Weixin\Application;


use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Wanphp\Libray\Slim\RedisCacheFactory;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\AccessTokenRepository;

class OAuthServerMiddleware implements MiddlewareInterface
{
  protected CacheItemPoolInterface $storage;
  private string $publicKeyPath;

  /**
   * @param ContainerInterface $container
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   */
  public function __construct(ContainerInterface $container)
  {
    //授权服务器分发的公钥
    $settings = $container->get(Setting::class)->get('oauth2Config');
    $this->publicKeyPath = realpath($settings['publicKey']);
    $this->storage = $container->get(RedisCacheFactory::class)->create($settings['database'] ?? 2, $settings['prefix'] ?? 'wp_uc');
  }

  /**
   * @throws InvalidArgumentException
   */
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $accessTokenRepository = new AccessTokenRepository($this->storage);

    $server = new ResourceServer($accessTokenRepository, $this->publicKeyPath);
    try {
      $request = $server->validateAuthenticatedRequest($request);

      // 验证scope
      $this->checkScopesWithLocalCache($request);

      return $handler->handle($request);
    } catch (OAuthServerException $exception) {
      return $exception->generateHttpResponse(new Response());
      // @codeCoverageIgnoreStart
    } catch (\Exception $exception) {
      return (new OAuthServerException($exception->getMessage(), 0, 'BadRequest'))
        ->generateHttpResponse(new Response());
      // @codeCoverageIgnoreEnd
    }
  }

  /**
   * @throws OAuthServerException
   * @throws InvalidArgumentException
   */
  private function checkScopesWithLocalCache(ServerRequestInterface $request): void
  {
    $path = $request->getUri()->getPath();
    $tokenScopes = $request->getAttribute('oauth_scopes', []);
    // 如果授权没限制scope，直接通过
    if (empty($tokenScopes)) return;

    $requiredScopes = [];
    $item = $this->storage->getItem('scopes');
    $scopes = $item->get();
    // 先精准匹配
    if (!empty($scopes[md5($path)])) {
      $requiredScopes = $scopes[md5($path)];
    } else {
      // 精准找不到，再用通配（如 /api/user/*）
      $segments = explode('/', trim($path, '/'));

      // 逐级往上找，例如
      // /api/user/info/avatar => /api/user/info/* => /api/user/* => /api/*
      for ($i = count($segments); $i > 0; $i--) {
        $candidate = '/' . implode('/', array_slice($segments, 0, $i)) . '/*';
        if (!empty($scopes[md5($candidate)])) {
          $requiredScopes = $scopes[md5($candidate)];
          break;
        }
      }
    }

    if (empty($requiredScopes)) {
      return; // 如果路径没限制scope，直接通过
    }

    $found = false;
    foreach ((array)$requiredScopes['scopes'] as $scope) {
      if (in_array($scope, $tokenScopes, true)) {
        $found = true;
        break;
      }
    }
    // 请求
    $type = str_split(str_pad((string)$requiredScopes['request'], 3, '0', STR_PAD_LEFT));
    $client = (int)$request->getAttribute('oauth_user_id', 0) == 0;
    $admin = in_array('ADMIN', $tokenScopes, true);
    switch ($request->getMethod()) {
      case 'GET':
        // 无读取权限
        if ($client && $type[0] < 4) $found = false;
        elseif ($admin && $type[1] < 4) $found = false;
        elseif ($type[2] < 4) $found = false;
        break;
      case 'DELETE':
        // 无删除权限
        if ($client && !in_array((int)$type[0], [5, 7])) $found = false;
        elseif ($admin && !in_array((int)$type[1], [5, 7])) $found = false;
        elseif (!in_array((int)$type[2], [5, 7])) $found = false;
        break;
      default:
        // 无写入权限
        if ($client && $type[0] < 6) $found = false;
        elseif ($admin && $type[1] < 6) $found = false;
        elseif ($type[2] < 6) $found = false;
    }

    if (!$found) {
      throw OAuthServerException::accessDenied('此请求的不在授权范围内');
    }
  }
}
