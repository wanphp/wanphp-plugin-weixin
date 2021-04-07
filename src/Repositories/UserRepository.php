<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/29
 * Time: 17:09
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\UserInterface;
use Wanphp\Plugins\Weixin\Entities\UserEntity;

class UserRepository extends BaseRepository implements UserInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLENAME, UserEntity::class);
  }

  public function findAll(): array
  {
    return $this->select();
  }

  public function findUserOfId(int $id): UserEntity
  {
    $user = $this->get('*', ['id' => $id]);
    if (empty($user)) throw new \Exception("找不到用户！");
    return new UserEntity($user);
  }


}
