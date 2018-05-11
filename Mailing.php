<?php

namespace common\components\mailing;

use Exception;
use Throwable;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException as InvalidParamException;
use yii\base\ViewContextInterface;
use yii\console\Application as ConsoleApplication;
use yii\di\Instance;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\log\Dispatcher;
use yii\log\Logger;
use yii\mail\MailerInterface;
use yii\queue\RetryableJobInterface;
use yii\web\Application as WebApplication;

/**
 * Class Mailing
 * @package common\components\mailing
 */
class Mailing extends Component implements BootstrapInterface, RetryableJobInterface, ViewContextInterface
{
    use IsTrait;

    /** @var string[] */
    public $from;
    /** @var string */
    public $controllerId;
    /** @var string */
    public $viewPath;
    /** @var string */
    public $assetPath;
    /** @var mixed */
    public $queue = 'queue';
    /** @var mixed */
    public $mailer = 'mailer';
    /** @var mixed */
    public $log = 'log';
    /** @var array */
    public $codes = [];
    /** @var string */
    public $deliveryClass = 'common\components\mailing\DeliveryAll';
    /** @var string */
    public $controllerClass = 'common\components\mailing\WebController';
    /** @var array */
    public $controllerOptions = [];
    /** @var string */
    public $commandClass = 'common\components\mailing\ConsoleController';
    /** @var array */
    public $commandOptions = [];
    /** @var string */
    public $home;

    /** @var array */
    protected $_target;
    /** @var Queue */
    protected $_queue;
    /** @var MailerInterface */
    protected $_mailer;
    /** @var Dispatcher */
    protected $_log;


    /**
     * @return array
     */
    public function getTarget()
    {
        return $this->_target;
    }

    /**
     * Собирает обработчики доставки.
     * @return Delivery[]
     */
    public function getDeliveries()
    {
        /** @var Delivery $class */
        $class = $this->deliveryClass;
        return $class::getDeliveries($this);
    }

    /**
     * @param array $id
     * @return Delivery|null
     */
    public function getDelivery($id)
    {
        /** @var Delivery $class */
        $class = $this->deliveryClass;
        return $class::getDelivery($this, $id);
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        /** @var Delivery $class */
        $class = $this->deliveryClass;
        return $class::getTags();
    }

    /**
     * @param string $email
     * @param int[] $tags
     * @param string[] $exclude
     * @param int $limit
     * @return array
     */
    public function searchEmails($email, array $tags = [], array $exclude = [], $limit = Delivery::LIMIT)
    {
        /** @var Delivery $class */
        $class = $this->deliveryClass;
        return $class::searchEmails($email, $tags, $exclude, $limit);
    }

    /**
     * Выполняет задачу по доставке отдельной цели.
     * @param Queue $queue
     * @throws Throwable
     */
    public function execute($queue)
    {
        $this->_queue = $queue;
        /** @var Delivery $class */
        $class = $this->deliveryClass;
        $delivery = $class::getDelivery($this, $this->_target['id']);
        if ($delivery !== null) {
            $target = $delivery->getTarget($this->_target['email']);
            if ($target !== null && $target->status !== Delivery::STATUS_PAUSE) {
                switch ($delivery->getStatus()) {
                    case Delivery::STATUS_PLAY:
                        $_exception = null;
                        try {
                            $target->execute($queue);
                            $delivery->updateTarget($target);
                        } catch (Exception $exception) {
                            $target->status = Delivery::STATUS_ERROR;
                            $delivery->updateTarget($target);
                            $_exception = $exception;
                        } catch (Throwable $exception) {
                            $target->status = Delivery::STATUS_ERROR;
                            $delivery->updateTarget($target);
                            $_exception = $exception;
                        }
                        if ($delivery->mayRestart()) {
                            $delivery->start();
                        }
                        if ($_exception) {
                            throw $_exception;
                        }
                        break;
                    case Delivery::STATUS_PAUSE:
                        $target->job = $queue->delay($this->getTtr())->push($this);
                        $delivery->updateTarget($target);
                        break;
                }
            }
        }
    }

    /**
     * Ставит задачу по доставке отдельной цели.
     * @param Queue $queue
     * @param int $delay
     * @param array $target
     * @return string
     */
    public function push($queue, $delay, $target)
    {
        $this->_target = $target;
        return $queue->delay($delay)->push($this);
    }

    /**
     * @return string
     */
    public function getHome()
    {
        return Url::to(empty($this->home) ? '@home' : $this->home);
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        return $this->_queue;
    }

    /**
     * @return MailerInterface
     */
    public function getMailer()
    {
        return $this->_mailer;
    }

    /**
     * @return int
     */
    public function getTtr()
    {
        return $this->_queue->ttr;
    }

    /**
     * @param int $attempt
     * @param Exception|Throwable $error
     * @return bool
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function canRetry($attempt, $error)
    {
        $this->_log->getLogger()->log($error, Logger::LEVEL_ERROR, $this->getControllerId());
        return $attempt < $this->_queue->attempts;
    }

    /**
     * @internal
     */
    public function __sleep()
    {
        return ['from', 'controllerId', 'queue', 'mailer', 'codes', 'commandClass', 'commandOptions', 'deliveryClass', '_target', 'home'];
    }

    /**
     * @internal
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function __wakeup()
    {
        $this->init();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!$this->is($this->controllerClass, WebController::className())) {
            throw new InvalidConfigException('Class name in controllerClass may implement ' . WebController::className());
        }
        if (!$this->is($this->commandClass, ConsoleController::className())) {
            throw new InvalidConfigException('Class name in commandClass may implement ' . ConsoleController::className());
        }
        if (!$this->is($this->deliveryClass, Delivery::className())) {
            throw new InvalidConfigException('Class name in deliveryClass may implement ' . Delivery::className());
        }
        $this->_queue = Instance::ensure($this->queue, Queue::className());
        $this->_mailer = Instance::ensure($this->mailer, 'yii\mail\MailerInterface');
        $this->_log = Instance::ensure($this->log, Dispatcher::className());
    }

    /**
     * @param Application $app
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function bootstrap($app)
    {
        $id = $this->getControllerId();
        if ($app instanceof ConsoleApplication) {
            $app->controllerMap["{$id}-queue"] = array_merge(
                [
                    'class' => $this->_queue->commandClass,
                    'queue' => $this->_queue,
                ],
                $this->_queue->commandOptions
            );
            $app->controllerMap[$id] = array_merge(
                [
                    'class' => $this->commandClass,
                    'mailing' => $this,
                ],
                $this->commandOptions
            );
        }
        if ($app instanceof WebApplication) {
            $app->controllerMap[$id] = array_merge(
                [
                    'class' => $this->controllerClass,
                    'mailing' => $this,
                ],
                $this->controllerOptions
            );
        }
    }

    /**
     * @return string command id
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getControllerId()
    {
        if (!empty($this->controllerId)) {
            return $this->controllerId;
        }
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        if (is_string($this->mailer)) {
            return Inflector::camel2id($this->mailer);
        }
        throw new InvalidConfigException('Mailing must be an application component.');
    }

    /**
     * @return string
     * @throws InvalidParamException
     */
    public function getViewPath()
    {
        return empty($this->viewPath)
            ? __DIR__ . DIRECTORY_SEPARATOR . 'view'
            : Yii::getAlias($this->viewPath);
    }

    /**
     * @return string
     * @throws InvalidParamException
     */
    public function getAssetPath()
    {
        return empty($this->assetPath)
            ? __DIR__ . DIRECTORY_SEPARATOR . 'view'
            : Yii::getAlias($this->assetPath);
    }
}