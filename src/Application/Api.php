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
 * @OA\Server(
 *   description="OpenApi host",
 *   url="https://www.wanphp.com/api"
 * )
 */

/**
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
 *   @OA\Property(property="code", type="string", example="400"),
 *   @OA\Property(property="error", type="string"),
 *   @OA\Property(property="error_description", type="string"),
 *   @OA\Property(property="hint", type="string"),
 *   @OA\Property(property="message", type="string", example="错误说明")
 * )
 */

/**
 * @OA\Schema(
 *   title="成功提示",
 *   schema="Success",
 *   required={"code", "message", "res"},
 *   @OA\Property(property="code", type="string", example="200"),
 *   @OA\Property(property="message", type="string", example="OK"),
 *   @OA\Property(property="datas", type="object",description="返回数据")
 * )
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Api
{
  /**
   * @var Request
   */
  protected $request;

  /**
   * @var Response
   */
  protected $response;

  /**
   * @var array
   */
  protected $args;

  /**
   * @param Request $request
   * @param Response $response
   * @param array $args
   * @return Response
   * @throws HttpNotFoundException
   * @throws HttpBadRequestException
   */
  public function __invoke(Request $request, Response $response, $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    try {
      return $this->action();
    } catch (\Exception $e) {
      throw new HttpBadRequestException($this->request, $e->getMessage());
    }
  }

  /**
   * @return Response
   * @throws HttpBadRequestException
   * @throws \Exception
   */
  abstract protected function action(): Response;

  /**
   * @return array|object
   * @throws HttpBadRequestException
   */
  protected function getFormData()
  {
    $input = json_decode(file_get_contents('php://input'));

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new HttpBadRequestException($this->request, 'JSON输入格式错误.');
    }

    return $input;
  }

  /**
   * @param string $name
   * @return mixed
   * @throws HttpBadRequestException
   */
  protected function resolveArg(string $name)
  {
    if (!isset($this->args[$name])) {
      throw new HttpBadRequestException($this->request, "找不到 `{$name}`.");
    }

    return $this->args[$name];
  }

  /**
   * @param array|object|null $data
   * @return Response
   */
  protected function respondWithData($data = null, int $statusCode = 200): Response
  {
    $json = json_encode(['code' => $statusCode, 'msg' => 'OK', 'datas' => $data], JSON_PRETTY_PRINT);
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
    $json = json_encode(['code' => $statusCode, 'msg' => $error], JSON_PRETTY_PRINT);
    $this->response->getBody()->write($json);

    return $this->respond($statusCode);
  }

  /**
   * @param $statusCode
   * @return Response
   */
  protected function respond($statusCode): Response
  {
    return $this->response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus($statusCode);
  }
}
