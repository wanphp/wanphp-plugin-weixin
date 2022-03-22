<?php
declare(strict_types=1);

namespace Wanphp\Plugins\Weixin\Domain;

use Wanphp\Libray\Mysql\BaseInterface;

interface UserLocationInterface extends BaseInterface
{
  const TABLENAME = "weixin_user_location";
}
