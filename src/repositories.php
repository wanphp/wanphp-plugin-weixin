<?php
declare(strict_types=1);

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    Wanphp\Libray\Weixin\MiniProgram::class => \DI\autowire(Wanphp\Libray\Weixin\MiniProgram::class)->constructor(\DI\get('wechat.miniprogram'), \DI\get('redis')),
    Wanphp\Libray\Weixin\Pay::class => \DI\autowire(Wanphp\Libray\Weixin\Pay::class)->constructor(\DI\get('wechat.pay-v2')),
    Wanphp\Libray\Weixin\WeChatPay::class => \DI\autowire(Wanphp\Libray\Weixin\WeChatPay::class)->constructor(\DI\get('wechat.pay-v3')),
    Wanphp\Libray\Weixin\WeChatBase::class => \DI\autowire(Wanphp\Libray\Weixin\WeChatBase::class)->constructor(\DI\get('wechat.base'), \DI\get('redis')),
    \Wanphp\Plugins\Weixin\Domain\CustomMenuInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\CustomMenuRepository::class),
    \Wanphp\Plugins\Weixin\Domain\MiniProgramInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\MiniProgramRepository::class),
    \Wanphp\Plugins\Weixin\Domain\MsgTemplateInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\MsgTemplateRepository::class),
    \Wanphp\Plugins\Weixin\Domain\PublicInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\PublicRepository::class),
    \Wanphp\Plugins\Weixin\Domain\UserInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\UserRepository::class),
    \Wanphp\Plugins\Weixin\Domain\UserLocationInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\UserLocationRepository::class)
  ]);
};
