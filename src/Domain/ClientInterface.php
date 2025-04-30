<?php

namespace Wanphp\Plugins\Weixin\Domain;

use Wanphp\Libray\Mysql\BaseInterface;

interface ClientInterface extends BaseInterface
{
  const TABLE_NAME = "clients";
}