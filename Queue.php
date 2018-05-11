<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\components\mailing;

use PDO;
use yii\base\Exception;
use yii\base\InvalidArgumentException as InvalidParamException;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use yii\queue\cli\LoopInterface;
use yii\queue\cli\Queue as CliQueue;

/**
 * Db Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    /**
     * @var Connection|array|string
     */
    public $db = 'db';
    /**
     * @var Mutex|array|string
     */
    public $mutex = 'mutex';
    /**
     * @var int timeout
     */
    public $mutexTimeout = 3;
    /**
     * @var string table name
     */
    public $tableName = '{{%queue}}';
    /**
     * @var string
     */
    public $channel = 'queue';
    /**
     * @var boolean ability to delete released messages from table
     */
    public $deleteReleased = true;
    /**
     * @var string command class name
     */
    public $commandClass = 'yii\queue\db\Command';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
        $this->mutex = Instance::ensure($this->mutex, Mutex::className());
    }

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $delay number of seconds to sleep before next iteration.
     * @return null|int exit code.
     * @internal for worker command only
     * @since 2.0.2
     */
    public function run($repeat, $delay = 0)
    {
        return $this->runWorker(function (LoopInterface $loop) use ($repeat, $delay) {
            while ($loop->canContinue()) {
                if ($payload = $this->reserve()) {
                    if ($this->handleMessage(
                        $payload['id'],
                        $payload['job'],
                        $payload['ttr'],
                        $payload['attempt']
                    )) {
                        $this->release($payload);
                    }
                }
                if (!$repeat) {
                    break;
                }
                if ($delay) {
                    sleep($delay);
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        $payload = (new Query())
            ->from($this->tableName)
            ->where(['id' => $id])
            ->one($this->db);

        if (!$payload) {
            if ($this->deleteReleased) {
                return self::STATUS_DONE;
            }

            throw new InvalidParamException("Unknown message ID: $id.");
        }

        if (!$payload['reserved_at']) {
            return self::STATUS_WAITING;
        }

        if (!$payload['done_at']) {
            return self::STATUS_RESERVED;
        }

        return self::STATUS_DONE;
    }

    /**
     * Clears the queue
     *
     * @since 2.0.1
     */
    public function clear()
    {
        $this->db->createCommand()
            ->delete($this->tableName, ['channel' => $this->channel])
            ->execute();
    }

    /**
     * Removes a job by ID
     *
     * @param int $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove($id)
    {
        return (bool) $this->db->createCommand()
            ->delete($this->tableName, ['channel' => $this->channel, 'id' => $id])
            ->execute();
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $this->db->createCommand()->insert($this->tableName, [
            'channel' => $this->channel,
            'pushed_at' => time(),
            'ttr' => $ttr,
            'delay' => $delay,
            'priority' => $priority ?: 1024,
        ])->execute();

        $tableSchema = $this->db->getTableSchema($this->tableName);
        $id = $this->db->getLastInsertID($tableSchema->sequenceName);

        $pdo = $this->db->getMasterPdo();
        $stmt = $pdo->prepare("update \"{$this->tableName}\" set \"job\" = empty_blob() where \"id\" = :id return \"job\" into :job");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':job', $job, PDO::PARAM_LOB | PDO::PARAM_INPUT_OUTPUT);
        $job = null;
        $stmt->execute();

        fwrite($job, $message);
        fclose($job);

        return $id;
    }

    /**
     * Takes one message from waiting list and reserves it for handling.
     *
     * @return array|false payload
     * @throws Exception in case it hasn't waited the lock
     */
    protected function reserve()
    {
        return $this->db->useMaster(function () {

            if (!$this->mutex->acquire(__CLASS__ . $this->channel, $this->mutexTimeout)) {
                throw new Exception("Has not waited the lock.");
            }
            $_exception = null;
            try {
                $this->moveExpired();

                // Reserve one message
                $payload = (new Query())
                    ->from($this->tableName)
                    ->andWhere(['channel' => $this->channel, 'reserved_at' => null])
                    ->andWhere('[[pushed_at]] <= :time - [[delay]]', [':time' => time()])
                    ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                    ->limit(1)
                    ->one($this->db);
                if (is_array($payload)) {
                    $payload['reserved_at'] = time();
                    $payload['attempt'] = (int)$payload['attempt'] + 1;
                    $this->db->createCommand()->update($this->tableName, [
                        'reserved_at' => $payload['reserved_at'], 'attempt' => $payload['attempt']],
                        ['id' => $payload['id']]
                    )->execute();

                    // pgsql
                    if (is_resource($payload['job'])) {
                        $payload['job'] = stream_get_contents($payload['job']);
                    }
                }
            } catch (\Exception $exception) {
                $_exception = $exception;
            } catch (\Throwable $exception) {
                $_exception = $exception;
            }

            $this->mutex->release(__CLASS__ . $this->channel);
            if ($_exception) {
                throw $_exception;
            }

            return $payload;
        });
    }



    /**
     * @param array $payload
     */
    protected function release($payload)
    {
        if ($this->deleteReleased) {
            $this->db->createCommand()->delete(
                $this->tableName,
                ['id' => $payload['id']]
            )->execute();
        } else {
            $this->db->createCommand()->update(
                $this->tableName,
                ['done_at' => time()],
                ['id' => $payload['id']]
            )->execute();
        }
    }

    /**
     * Moves expired messages into waiting list.
     */
    private function moveExpired()
    {
        if ($this->reserveTime !== time()) {
            $this->reserveTime = time();
            $this->db->createCommand()->update(
                $this->tableName,
                ['reserved_at' => null],
                '[[reserved_at]] < :time - [[ttr]] and [[done_at]] is null',
                [':time' => $this->reserveTime]
            )->execute();
        }
    }

    private $reserveTime;
}