<?php

namespace Wanphp\Plugins\Weixin\Entities\OAuth2;


use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ScopeEntity implements ScopeEntityInterface
{
  use EntityTrait;

  // 没有 Trait 实现这个方法，需要自行实现
  // oauth2-server 项目的测试代码的实现例子
  public function jsonSerialize(): mixed
  {
    return $this->getIdentifier();
  }
}
