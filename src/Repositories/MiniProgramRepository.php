<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:54
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\MiniProgramInterface;
use Wanphp\Plugins\Weixin\Entities\MiniProgramEntity;

class MiniProgramRepository extends BaseRepository implements MiniProgramInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLENAME, MiniProgramEntity::class);
  }
}
