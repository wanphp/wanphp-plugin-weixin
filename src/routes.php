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

  // 后台管理
  $app->group('/admin/weixin', function (Group $group) {
    // 用户基本信息管理
    $group->map(['GET', 'PUT', 'PATCH'], '/user[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\UserApi::class);
    //公众号自定义菜单
    $group->map(['GET', 'PUT', 'POST', 'DELETE'], '/menu[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\CustomMenuApi::class);
    $group->post('/createMenu', \Wanphp\Plugins\Weixin\Application\Manage\CreateMenuApi::class);
    //用户标签
    $group->map(['GET', 'PUT', 'POST', 'DELETE'], '/tags[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\TagsApi::class);
    //公众号模板消息
    $group->map(['GET', 'POST', 'DELETE'], '/tplmsg[/{tplid}]', \Wanphp\Plugins\Weixin\Application\Manage\TemplateMessageApi::class);
    //公众号粉丝打标签
    $group->map(['GET', 'POST'], '/user/tag[/{openid}]', \Wanphp\Plugins\Weixin\Application\Manage\UserTagApi::class);
    $group->delete('/user/{openid}/tag/{tagid}', \Wanphp\Plugins\Weixin\Application\Manage\UserTagApi::class);
    $group->get('/users/search', \Wanphp\Plugins\Weixin\Application\Manage\SearchUserApi::class);
  })->addMiddleware($PermissionMiddleware);
  // Api 接口
  $app->group('', function (Group $group) use ($PermissionMiddleware) {
    // 当前用户
    $group->map(['GET', 'PATCH'], '/api/user', \Wanphp\Plugins\Weixin\Application\UserApi::class);
    // 发送消息
    $group->post('api/user/sendMsg',\Wanphp\Plugins\Weixin\Application\SendTemplateMessageApi::class);
    // 客户端添加修改用户
    $group->map(['POST', 'PUT'], '/api/user[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\UserApi::class);
    // 客户端搜索用户
    $group->get('/api/user/search', \Wanphp\Plugins\Weixin\Application\Manage\SearchUserApi::class);
    // 客户端通过用户id获取用户
    $group->map(['POST', 'GET'], '/api/user/get[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\GetUsersApi::class);
  })->addMiddleware($OAuthServerMiddleware);
};


