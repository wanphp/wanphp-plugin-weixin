<?php

namespace Wanphp\Plugins\Weixin\Entities\OAuth2;


use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
  use AccessTokenTrait, TokenEntityTrait, EntityTrait;
}
