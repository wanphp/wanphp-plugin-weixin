<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/17
 * Time: 15:24
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\UserRoleInterface;
use Wanphp\Plugins\Weixin\Entities\UserRoleEntity;

class UserRoleRepository extends BaseRepository implements UserRoleInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLENAME, UserRoleEntity::class);
  }
}
