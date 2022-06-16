<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:46
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Entities\PublicEntity;

class PublicRepository extends BaseRepository implements PublicInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLE_NAME, PublicEntity::class);
  }
}
