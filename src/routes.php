<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Server\MiddlewareInterface as Middleware;

return function (App $app, Middleware $PermissionMiddleware, Middleware $OAuthServerMiddleware) {
  //公众号绑定，使用时按实际情况重写方法
  //$app->map(['GET', 'POST'], '/weixin', \Wanphp\Plugins\Weixin\Application\WePublic::class);
  //支付通知，使用时按实际情况重写方法
  //$app->post('/paynotice', \Wanphp\Plugins\Weixin\Application\PayNotice::class);
  //公众号分享签名
  $app->post('/getSignPackage', \Wanphp\Plugins\Weixin\Application\ShareApi::class);

  $app->group('/api', function (Group $group) use ($PermissionMiddleware) {
    // 当前用户
    $group->map(['GET', 'PATCH'], '/user', \Wanphp\Plugins\Weixin\Application\UserApi::class);
    //
    $group->group('/manage/weixin', function (Group $g) {
      // 用户
      $g->map(['GET', 'PATCH'], '/users[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\UserApi::class);
      // 用户角色
      $g->map(['GET', 'PUT', 'POST', 'DELETE'], '/user/role[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\UserRoleApi::class);
      //公众号自定义菜单
      $g->map(['GET', 'PUT', 'POST', 'DELETE'], '/menu[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\CustomMenuApi::class);
      $g->post('/createMenu', \Wanphp\Plugins\Weixin\Application\Manage\CreateMenuApi::class);
      //用户标签
      $g->map(['GET', 'PUT', 'POST', 'DELETE'], '/tag[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\TagsApi::class);
      //公众号模板消息
      $g->map(['GET', 'POST', 'DELETE'], '/tplmsg[/{tplid}]', \Wanphp\Plugins\Weixin\Application\Manage\TemplateMessageApi::class);
      //公众号粉丝打标签
      $g->map(['GET', 'POST'], '/user/tag[/{openid}]', \Wanphp\Plugins\Weixin\Application\Manage\UserTagApi::class);
      $g->delete('/user/{openid}/tag/{tagid}', \Wanphp\Plugins\Weixin\Application\Manage\UserTagApi::class);
    })->addMiddleware($PermissionMiddleware);
  })->addMiddleware($OAuthServerMiddleware);
};


