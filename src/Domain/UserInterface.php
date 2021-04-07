<?php
declare(strict_types=1);

namespace Wanphp\Plugins\Weixin\Domain;

use Wanphp\Libray\Mysql\BaseInterface;
use Wanphp\Plugins\Weixin\Entities\UserEntity;

interface UserInterface extends BaseInterface
{
  const TABLENAME = "weixin_users";

  /**
   * @return array
   * @throws \Exception
   */
  public function findAll(): array;

  /**
   * @param int $id
   * @return UserEntity
   * @throws \Exception
   */
  public function findUserOfId(int $id): UserEntity;
}
