<?php

namespace common\components\mailing;

use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Соборщик адресов доставки.
 * Берет теги и адреса из конфигурации рассылки, собирает список адресов.
 * @package common\components\mailing
 */
class Combinator extends BaseObject implements JobInterface
{
    /** @var Delivery */
    public $delivery;
    /** @var int[] Сборник правил комбинации целейц доставки. */
    public $tags = [];
    /** @var string[] Сборник дополнительных целей доставки. */
    public $emails = [];


    /**
     * @internal
     */
    public function __sleep()
    {
        return ['tags', 'emails'];
    }

    /**
     * @internal
     */
    public function __wakeup()
    {
        $this->init();
    }

    /**
     * Регистрация задач на цели доставки.
     * @param Queue $queue
     */
    public function execute($queue)
    {
        $targets = $this->delivery->fetchTargets($this);
        foreach ($targets as $target) {
            $delay = $this->delivery->getNextDate() - time();
            $target->job = $this->delivery->mailing->push($queue, $delay, [
                'id' => $this->delivery->getId($target),
                'email' => $target->email
            ]);
        }
        $this->delivery->setTargets($targets);
    }
}