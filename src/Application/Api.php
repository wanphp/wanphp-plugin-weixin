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
 *     description="wanphp 微信扩展接口",
 *     version="1.0.0",
 *     title="微信扩展接口"
 * )
 */

/**
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
 */

/**
 * @OA\Schema(
 *   title="出错提示",
 *   schema="Error",
 *   type="object"
 * )
 */

/**
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

abstract class Api
{
  /**
   * @var Request
   */
  protected Request $request;

  /**
   * @var Response
   */
  protected Response $response;

  /**
   * @var array
   */
  protected array $args;

  /**
   * @param Request $request
   * @param Response $response
   * @param array $args
   * @return Response
   * @throws HttpBadRequestException
   */
  public function __invoke(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    try {
      return $this->action();
    } catch (Exception $e) {
      throw new HttpBadRequestException($this->request, $e->getMessage());
    }
  }

  /**
   * @return Response
   * @throws HttpBadRequestException
   * @throws Exception
   */
  abstract protected function action(): Response;

  /**
   * @param array $data
   * @param int $statusCode
   * @return Response
   */
  protected function respondWithData(array $data = [], int $statusCode = 200): Response
  {
    $json = json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE);
    $this->response->getBody()->write($json);

    return $this->respond($statusCode);
  }

  /**
   * @param null $error
   * @param int $statusCode
   * @return Response
   */
  protected function respondWithError($error = null, int $statusCode = 400): Response
  {
    $json = json_encode(['errMsg' => $error], JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE);
    $this->response->getBody()->write($json);

    return $this->respond($statusCode);
  }

  /**
   * @param $statusCode
   * @return Response
   */
  protected function respond($statusCode): Response
  {
    return $this->response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
  }
}
