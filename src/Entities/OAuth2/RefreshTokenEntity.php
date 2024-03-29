<?php

namespace Wanphp\Plugins\Weixin\Entities\OAuth2;


use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

class RefreshTokenEntity implements RefreshTokenEntityInterface
{
  use RefreshTokenTrait, EntityTrait;
}
