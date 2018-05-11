<?php

namespace common\components\mailing;

use Exception;
use yii\base\Action;
use yii\console\Controller;
use yii\console\widgets\Table;

/**
 * Управление рассылками.
 * @package common\components\mailing
 */
class ConsoleController extends Controller
{
    const LIMIT = 10;

    /** @var Mailing Майлер-компонент. */
    public $mailing;
    /** @var int Лимит элементов для вывода. */
    public $limit = self::LIMIT;
    /** @var int Смещение элементов для вывода. */
    public $offset = 0;
    /** @var bool Выполнение на целях требующих перезапуск. */
    public $auto = true;


    /**
     * @param Delivery[] $deliveries
     * @param string $title
     * @throws Exception
     */
    protected function echoDeliveries($deliveries, $title = 'Цели рассылок')
    {
        $count = count($deliveries);
        $bottom = min($count, $this->offset + 1);
        $top = min($count, $this->offset + $this->limit);
        $this->stdout(sprintf(
            "%s: %s-%s из %s\n",
            $title,
            number_format($bottom, 0, '.', ' '),
            number_format($top, 0, '.', ' '),
            number_format($count, 0, '.', ' ')
        ));
        $rows = [];
        /** @var Delivery[] $tableDeliveries */
        $tableDeliveries = array_slice($deliveries, $this->offset, $this->limit, true);
        foreach ($tableDeliveries as $delivery => $deliveryManager) {
            $title = $deliveryManager->getName();
            $latestDate = $deliveryManager->getLatestDate();
            $nextDate = $deliveryManager->getNextDate();
            $countReady = $deliveryManager->countTotal([Delivery::STATUS_STOP]);
            $countTotal = $deliveryManager->countTotal();
            $countError = $deliveryManager->countTotal([Delivery::STATUS_ERROR]);
            $percent = $countTotal !== 0 ? $countReady / $countTotal * 100 : '-';
            $rows[] = [
                $delivery,
                $title,
                $latestDate !== null ? date('d.m.Y', $latestDate) : '-',
                $nextDate !== null ? date('d.m.Y', $nextDate) : '-',
                $countTotal !== 0
                    ? number_format($percent, 0, '.', ' ') . '%'
                    : $percent,
                number_format($countTotal, 0, '.', ' '),
                number_format($countReady, 0, '.', ' '),
                number_format($countError, 0, '.', ' ')
            ];
        }
        echo Table::widget([
            'headers' => ['#', 'Цель', 'Запущено', 'Запланировано', 'Статус', 'Всего', 'Выполнено', 'Ошибок'],
            'rows' => $rows
        ]);
    }

    /**
     * @param string[] $deliveries
     * @return Delivery[]
     */
    protected function filter(array $deliveries = [])
    {
        $include = [];
        $exclude = [];
        foreach ($deliveries as $delivery) {
            if (strpos($delivery, '!') === 0) {
                $exclude[] = (int) substr($delivery, 1);
            } else {
                $include[] = (int) $delivery;
            }
        }
        $result = $this->mailing->getDeliveries();
        if (count($include) > 0) {
            $result = array_filter($result, function ($key) use ($include) {
                return in_array($key, $include, true);
            }, ARRAY_FILTER_USE_KEY);
        }
        if (count($exclude) > 0) {
            $result = array_filter($result, function ($key) use ($exclude) {
                return !in_array($key, $exclude, true);
            }, ARRAY_FILTER_USE_KEY);
        }
        return $result;
    }


    /**
     * @param Action $action
     * @return array
     */
    public function getActionArgsHelp($action)
    {
        $arguments = parent::getActionArgsHelp($action);
        if (in_array($action->id, ['index', 'pause', 'resume', 'start', 'stop', 'refresh'])) {
            $arguments['...'] = [
                'required' => false,
                'type' => 'Идентификаторы целей доставки',
                'default' => 'Все цели',
                'comment' => 'Список целей доставки. Используйте символ "!" для исключения.',
            ];
        }
        return $arguments;
    }

    /**
     * @return string[]
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'l' => 'limit',
            'o' => 'offset',
        ]);
    }

    /**
     * @param string $action
     * @return string[]
     */
    public function options($action)
    {
        return in_array($action, ['index', 'run', 'stop'])
            ? array_merge(parent::options($action), ['limit', 'offset'])
            : parent::options($action);
    }

    /**
     * Показать состояние рассылок.
     * @throws Exception
     */
    public function actionIndex()
    {
        $deliveries = $this->filter(func_get_args());
        $this->echoDeliveries($deliveries);
    }

    /**
     * Перезапуск выполненных переодических рассылок.
     * @throws Exception
     */
    public function actionRefresh()
    {
        $deliveries = $this->filter(func_get_args());
        foreach ($deliveries as $deliveryManager) {
            if ($deliveryManager->mayRestart()) {
                $deliveryManager->start();
            }
        }
        $this->echoDeliveries($deliveries, 'Отправлены на повторное выполнение переодические рассылки');
    }

    /**
     * Запуск рассылок.
     * @throws Exception
     */
    public function actionStart()
    {
        $deliveries = $this->filter(func_get_args());
        foreach ($deliveries as $deliveryManager) {
            $deliveryManager->start();
        }
        $this->echoDeliveries($deliveries, 'Отправлены на выполнение цели рассылок');
    }

    /**
     * Остановка рассылок.
     * @throws Exception
     */
    public function actionStop()
    {
        $deliveries = $this->filter(func_get_args());
        foreach ($deliveries as $deliveryManager) {
            $deliveryManager->stop();
        }
        $this->echoDeliveries($deliveries, 'Сняты с выполнения цели рассылок');
    }

    /**
     * Возобновление рассылок.
     * @throws Exception
     */
    public function actionResume()
    {
        $deliveries = $this->filter(func_get_args());
        foreach ($deliveries as $deliveryManager) {
            $deliveryManager->resume();
        }
        $this->echoDeliveries($deliveries, 'Востановлено выполнение целей рассылок');
    }

    /**
     * Приостановка рассылок.
     * @throws Exception
     */
    public function actionPause()
    {
        $deliveries = $this->filter(func_get_args());
        foreach ($deliveries as $deliveryManager) {
            $deliveryManager->pause();
        }
        $this->echoDeliveries($deliveries, 'Приастановлено выполнение целей рассылок');
    }
}