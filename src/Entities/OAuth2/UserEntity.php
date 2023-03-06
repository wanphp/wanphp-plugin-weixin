<?php

namespace Wanphp\Plugins\Weixin\Entities\OAuth2;


use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface
{
  use EntityTrait;
}
