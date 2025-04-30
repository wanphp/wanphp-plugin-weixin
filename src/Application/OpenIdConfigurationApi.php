<?php

namespace Wanphp\Plugins\Weixin\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Slim\Setting;

class OpenIdConfigurationApi extends Api
{
  private array $openid_configuration;

  public function __construct(Setting $setting)
  {
    $this->openid_configuration = (array)$setting->get('openid_configuration');
  }

  /**
   * @inheritDoc
   */
  protected function action(): Response
  {
    $data = [
      "issuer" => $this->httpHost(),
      "authorization_endpoint" => $this->httpHost() . '/auth/authorize',
      "token_endpoint" => $this->httpHost() . '/auth/accessToken',
      "userinfo_endpoint" => $this->httpHost() . '/api/userProfile',
    ];
    return $this->respondWithData(array_merge($data, $this->openid_configuration));
  }
}