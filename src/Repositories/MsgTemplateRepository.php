<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 15:05
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Exception;
use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\MsgTemplateInterface;
use Wanphp\Plugins\Weixin\Entities\MsgTemplateEntity;

class MsgTemplateRepository extends BaseRepository implements MsgTemplateInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLE_NAME, MsgTemplateEntity::class);
  }

  /**
   * @param $id
   * @return string
   * @throws Exception
   */
  public function getTemplateId($id): string
  {
    return $this->get('template_id', ['id' => $id, 'status' => 1]);
  }
}
