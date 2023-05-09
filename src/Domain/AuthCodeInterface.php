<?php

namespace Wanphp\Plugins\Weixin\Domain;

use Wanphp\Libray\Mysql\BaseInterface;

interface AuthCodeInterface extends BaseInterface
{
  const TABLE_NAME = "authCode";
}
