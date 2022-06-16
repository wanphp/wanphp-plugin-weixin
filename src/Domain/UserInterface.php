<?php
declare(strict_types=1);

namespace Wanphp\Plugins\Weixin\Domain;

use Wanphp\Libray\Mysql\BaseInterface;

interface UserInterface extends BaseInterface
{
  const TABLE_NAME = "weixin_users";

  public function getUser($id);

  public function getUsers($where);
}
