<?php

namespace common\components\mailing;

use Exception;
use Yii;
use yii\queue\ExecEvent;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class WebController
 * @package common\components\mailing
 */
abstract class WebController extends Controller
{
    /** @var Mailing */
    public $mailing;

    /**
     * @return string
     */
    public function actionMailing()
    {
        $queue = $this->mailing->getQueue();
        $result = [];
        $attempts = $queue->attempts;
        $queue->on(Queue::EVENT_BEFORE_EXEC, function (ExecEvent $event) use (&$result) {
            $time = time();
            if ($event->job instanceof Mailing) {
                $target = $event->job->getTarget();
                $id = $target['email'];
            } else {
                $id = $event->id;
            }
            $result[] = "[{$time}]\tВыполняется {$id}. Попытка {$event->attempt}.";
        });
        $queue->on(Queue::EVENT_AFTER_EXEC, function (ExecEvent $event) use (&$result) {
            $time = time();
            if ($event->job instanceof Mailing) {
                $target = $event->job->getTarget();
                $id = $target['email'];
            } else {
                $id = $event->id;
            }
            $result[] = "[{$time}]\tУспешно завершено {$id}.";
        });
        $queue->on(Queue::EVENT_AFTER_ERROR, function (ExecEvent $event) use (&$result, $attempts) {
            $time = time();
            if ($event->job instanceof Mailing) {
                $target = $event->job->getTarget();
                $id = $target['email'];
            } else {
                $id = $event->id;
            }
            if ($event->attempt < $attempts) {
                $result[] = "[{$time}]\tОшибка выполнения {$id}. Отложено на {$event->ttr} секунд.";
            } else {
                $result[] = "[{$time}]\tОшибка выполнения {$id}. Попытки исчерпаны.";
            }
        });
        $queue->run(false);
        if (empty($result)) {
            $time = time();
            $result[] = "[{$time}]\tЕще нечего выполнять.";
        }
        return implode("\n", array_reverse($result)) . "\n";
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function actionDelivery()
    {
        $request = Yii::$app->request;
        $delivery = $request->post('delivery', null);
        $statuses = $request->post('statuses', []);
        $action = $request->post('action', 'update');
        $readonly = $request->post('readonly', 'false') !== 'false';
        if ($delivery !== null) {
            $delivery = $this->mailing->getDelivery($delivery);
            $name = $delivery->getName();
            $alert = '';
            switch ($action) {
                case 'start':
                    $alert = $delivery->start() ? '' : "Невозможно запустить рассылку {$name}";
                    break;
                case 'resume':
                    $alert = $delivery->resume() ? '' : "Невозможно возобновить рассылку {$name}";
                    break;
                case 'stop':
                    $alert = $delivery->stop() ? '' : "Невозможно остановить рассылку {$name}";
                    break;
                case 'pause':
                    $alert = $delivery->pause() ? '' : "Невозможно приостановить рассылку {$name}";
                    break;
            }
        }
        return $this->responseAjax([
            'alert' => empty($alert) ? false : $alert,
            'content' => $delivery === null ? '' : DeliveryWidget::widget([
                'model' => $delivery->model,
                'readonly' => $readonly,
                'statuses' => $statuses,
            ]),
        ]);
    }

    /**
     * @return Response
     */
    public function actionCombinator()
    {
        $request = Yii::$app->request;
        $state = $request->post('state', null);
        $query = $request->post('query', '');
        $tags = $request->post('tags', []);
        $limit = (int) $request->post('limit', Delivery::LIMIT);
        return $state === null ? $this->responseSearch($query, $tags, $limit) : $this->responseSerialize($state);
    }

    public function actionList()
    {
        $request = Yii::$app->request;
        $search = $request->post('search', '');
        $selected = array_filter(array_map(function ($item) {
            return is_numeric($item) ? (int) $item : null;
        }, (array) $request->post('selected', [])));
        $list = $request->post('list', '');
        switch ($list) {
            case 'news': return $this->responseAjax($this->findNews($search, $selected));
            default: return $this->responseAjax([]);
        }
    }

    /**
     * @param string $search
     * @param int[] $selected
     * @return mixed
     */
    abstract public function findNews($search, $selected);


    /**
     * @param string $query
     * @param array $tags
     * @param int $limit
     * @return Response
     */
    protected function responseSearch($query, $tags, $limit)
    {
        $selectTags = array_filter($this->mailing->getTags(), function ($tag) use ($tags) {
            return in_array($tag, $tags, true);
        });
        $selectEmails = array_filter($tags, function ($tag) use ($selectTags) {
            return !in_array($tag, $selectTags, true);
        });
        $emails = $this->mailing->searchEmails($query, array_keys($selectTags), $selectEmails, $limit);
        if (count($emails) === 0) {
            $emails = $this->mailing->searchEmails($query, [], $selectEmails, $limit);
        }
        return $this->responseAjax($emails);
    }

    /**
     * @param array $state
     * @return Response
     */
    protected function responseSerialize($state)
    {
        $selectTags = array_filter($this->mailing->getTags(), function ($tag) use ($state) {
            return in_array($tag, $state, true);
        });
        $selectEmails = array_filter($state, function ($tag) use ($selectTags) {
            return !in_array($tag, $selectTags, true);
        });
        $combiner = new Combinator([
            'tags' => array_keys($selectTags),
            'emails' => $selectEmails
        ]);
        return $this->responseAjax(['combiner' => $this->mailing->getQueue()->serializer->serialize($combiner)]);
    }

    /**
     * @param mixed $data
     * @return Response
     */
    protected function responseAjax($data)
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $response->data = $data;
        return $response;
    }
}