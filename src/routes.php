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
  // OpenID Connect 基本配置
  $app->get('/.well-known/openid-configuration', \Wanphp\Plugins\Weixin\Application\OpenIdConfigurationApi::class);

  $app->group('/admin', function (Group $group) {
    // 客户端管理
    $group->map(['GET', 'PUT', 'PATCH', 'POST', 'DELETE'], '/clients[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\ClientsApi::class);
    $group->map(['GET', 'PUT', 'POST', 'DELETE'], '/client/scopes[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\ScopesApi::class);
  })->addMiddleware($PermissionMiddleware);
  $app->group('/auth', function (Group $group) {
    $group->map(['GET', 'POST'], '/authorize', \Wanphp\Plugins\Weixin\Application\Auth\AuthorizeApi::class);
    $group->post('/accessToken', \Wanphp\Plugins\Weixin\Application\Auth\AccessTokenApi::class);
    $group->post('/passwordAccessToken', \Wanphp\Plugins\Weixin\Application\Auth\AccessTokenApi::class);
    $group->post('/refreshAccessToken', \Wanphp\Plugins\Weixin\Application\Auth\AccessTokenApi::class);
    $group->map(['GET', 'POST'], '/qrLogin', \Wanphp\Plugins\Weixin\Application\Auth\QrLoginApi::class);
  });
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
    $group->map(['GET', 'PATCH', 'DELETE'], '/user/tag[/{openid}]', \Wanphp\Plugins\Weixin\Application\Manage\UserTagApi::class);
    $group->get('/users/search', \Wanphp\Plugins\Weixin\Application\Manage\SearchUserApi::class);
    // 关键词自动回复
    $group->map(['GET', 'PUT', 'POST', 'DELETE'], '/autoReply[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\Manage\AutoReplyApi::class);
    // 取自定义菜单事件
    $group->get('/autoReply/getEvent[/{type:click|view}]', \Wanphp\Plugins\Weixin\Application\Manage\AutoReplyApi::class . ':getEvent');
    // 设置公众号Cookie
    $group->post('/setCookie', \Wanphp\Plugins\Weixin\Application\Manage\UserApi::class . ':setCookie');
    // 素材管理
    $group->post('/material/add/{type:image|voice|video|thumb}', \Wanphp\Plugins\Weixin\Application\Manage\MaterialApi::class);
    $group->delete('/material/del/{media_id}', \Wanphp\Plugins\Weixin\Application\Manage\MaterialApi::class);
    $group->get('/material/list[/{type:image|video|voice}]', \Wanphp\Plugins\Weixin\Application\Manage\MaterialApi::class);
    $group->get('/materialDialog[/{type:image|video|voice}]', \Wanphp\Plugins\Weixin\Application\Manage\MaterialApi::class . ':dialog');
    $group->get('/material/voice/{media_id}', \Wanphp\Plugins\Weixin\Application\Manage\MaterialApi::class . ':media');
    $group->get('/material/image/{media_id}', \Wanphp\Plugins\Weixin\Application\Manage\MaterialApi::class . ':media');
    $group->get('/material/video/{media_id}', \Wanphp\Plugins\Weixin\Application\Manage\MaterialApi::class . ':video');
  })->addMiddleware($PermissionMiddleware);
  // Api 接口
  $app->group('/api', function (Group $group) use ($PermissionMiddleware) {
    // 当前用户
    $group->get('/userProfile', \Wanphp\Plugins\Weixin\Application\UserApi::class . ':user');
    $group->map(['GET', 'PATCH'], '/user', \Wanphp\Plugins\Weixin\Application\UserApi::class);
    // 发送消息
    $group->post('/user/sendMsg', \Wanphp\Plugins\Weixin\Application\SendTemplateMessageApi::class);
    // 客户端添加修改用户
    $group->map(['POST', 'PUT'], '/user[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\UserApi::class);
    // 客户端搜索用户
    $group->get('/user/search', \Wanphp\Plugins\Weixin\Application\Manage\SearchUserApi::class);
    // 客户端通过用户id获取用户
    $group->map(['POST', 'GET'], '/user/get[/{id:[0-9]+}]', \Wanphp\Plugins\Weixin\Application\GetUsersApi::class);
    // 客户端给公众号粉丝打标签
    $group->map(['PATCH', 'DELETE'], '/user/tag', \Wanphp\Plugins\Weixin\Application\Manage\UserTagApi::class);
    // 用户注销账号
    $group->post('/logOutAccount', \Wanphp\Plugins\Weixin\Application\UserApi::class . ':logOutAccount');
  })->addMiddleware($OAuthServerMiddleware);
};


