<?php
declare(strict_types=1);

namespace Wanphp\Plugins\Weixin\Domain;

use Wanphp\Libray\Mysql\BaseInterface;
use Wanphp\Libray\Slim\WpUserInterface;

interface UserInterface extends BaseInterface, WpUserInterface
{
  const TABLE_NAME = "weixin_users";

  public function getUserList($params): array;

  public function getUserCount($where): int;
}
