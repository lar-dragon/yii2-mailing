<?php

namespace common\components\mailing;

use yii\base\BaseObject;
use yii\db\ActiveRecord;
use yii\db\Exception;

/**
 * Trait DeliveryCountTrite
 * @package common\components\mailing
 */
abstract class Delivery extends BaseObject
{
    // Статусы состояния
    const STATUS_STOP  = 0; // и для целей
    const STATUS_PLAY  = 1; // и для целей
    const STATUS_ERROR = 2; // и для целей
    const STATUS_PAUSE = -1;
    const LIMIT = 10;

    /** @var Mailing */
    public $mailing;
    /** @var ActiveRecord */
    public $model;


    /**
     * @param Mailing $mailing
     * @return Delivery[]
     */
    public static function getDeliveries($mailing)
    {
        return [];
    }

    /**
     * @param Mailing $mailing
     * @param array $id
     * @return null|Delivery
     */
    public static function getDelivery($mailing, $id)
    {
        return null;
    }

    /**
     * @return string[]
     */
    public static function getTags()
    {
        return [];
    }

    /**
     * @param string $email
     * @param int[] $tags
     * @param string[] $exclude
     * @param int $limit
     * @return array
     */
    public static function searchEmails($email, array $tags = [], array $exclude = [], $limit = Delivery::LIMIT)
    {
        return [];
    }

    /**
     * @return bool
     */
    public function start()
    {
        try {
            /** @var ActiveRecord $class */
            $class = get_class($this->model);
            $transaction = $class::getDb()->beginTransaction();
            if (
                $this->updateDates() &&
                $this->setStatus(self::STATUS_PLAY)
            ) {
                $this->getCombinator()->execute($this->mailing->getQueue());
                $transaction->commit();
                return true;
            }
            $transaction->rollBack();
            return false;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function stop()
    {
        try {
            /** @var ActiveRecord $class */
            $class = get_class($this->model);
            $transaction = $class::getDb()->beginTransaction();
            if (
                $this->setStatus(self::STATUS_STOP)
            ) {
                $this->setTargets([]);
                $transaction->commit();
                return true;
            }
            $transaction->rollBack();
            return false;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function pause()
    {
        return $this->setStatus(self::STATUS_PAUSE);
    }

    /**
     * @return bool
     */
    public function resume()
    {
        return $this->setStatus(self::STATUS_PLAY);
    }

    /**
     * @return array
     */
    abstract public function getId();

    /**
     * @param Target[] $targets
     * @return bool
     */
    abstract public function setTargets($targets);

    /**
     * @param Target $target
     * @return bool
     */
    abstract public function updateTarget($target);

    /**
     * @return Target[]
     */
    abstract public function getTargets();

    /**
     * @param string $email
     * @return Target
     */
    abstract public function getTarget($email);

    /**
     * @param Combinator $combinator
     * @return Target[]
     */
    abstract public function fetchTargets($combinator);

    /**
     * @param null|int|int[] $status
     * @return int
     */
    abstract public function countTotal($status = null);

    /**
     * @param Combinator $combinator
     * @return bool
     */
    abstract public function setCombinator($combinator);

    /**
     * @return Combinator
     */
    abstract public function getCombinator();

    /**
     * @param int $status
     * @return bool
     */
    abstract public function setStatus($status);

    /**
     * @return int
     */
    abstract public function getStatus();

    /**
     * @return bool
     */
    abstract public function updateDates();

    /**
     * @return int
     */
    abstract public function getLatestDate();

    /**
     * @return int
     */
    abstract public function getNextDate();

    /**
     * @param string $name
     * @return boolean
     */
    abstract public function setName($name);

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return string
     */
    abstract public function getSubject();

    /**
     * @return string
     */
    abstract public function getBody();

    /**
     * @return bool
     */
    abstract public function mayRestart();
}