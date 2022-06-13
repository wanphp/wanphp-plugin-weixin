<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/25
 * Time: 10:49
 */

namespace Wanphp\Plugins\Weixin\Application;

/**
 * @OA\Info(
 *     description="微信开发常用操作，插件不能单独运行",
 *     version="1.1.0",
 *     title="微信开发插件"
 * )
 * @OA\Tag(
 *     name="Public",
 *     description="公共操作接口",
 * )
 * @OA\Tag(
 *     name="UserRole",
 *     description="用户角色",
 * )
 * @OA\Tag(
 *     name="User",
 *     description="用户操作接口",
 * )
 */

/**
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 * )
 * @OA\Schema(
 *   title="出错提示",
 *   schema="Error",
 *   type="object"
 * )
 * @OA\Schema(
 *   title="成功提示",
 *   schema="Success",
 *   type="object"
 * )
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Exception;
use Wanphp\Libray\Slim\Action;

abstract class Api extends Action
{

}
