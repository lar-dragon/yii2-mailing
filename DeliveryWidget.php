<?php

namespace common\components\mailing;

use Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException as InvalidParamException;
use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\di\Instance;
use yii\helpers\Url;
use yii\web\View;

class DeliveryWidget extends Widget
{
    use IsTrait;

    /** @var mixed */
    public $mailing = 'mailing';
    /** @var bool */
    public $readonly = false;
    /** @var string[] */
    public $statuses = [
        Delivery::STATUS_STOP  => 'Остановлено',
        Delivery::STATUS_PLAY  => 'Выполняется',
        Delivery::STATUS_ERROR => 'Ошибка',
        Delivery::STATUS_PAUSE => 'Приостановлено',
    ];
    /** @var ActiveRecord */
    public $model;

    /** @var Mailing */
    protected $_mailing;
    /** @var Delivery */
    protected $_delivery;


    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function init()
    {
        parent::init();
        $this->_mailing = Instance::ensure($this->mailing, Mailing::className());
        if ($this->model instanceof ActiveRecord) {
            if (!$this->model->isNewRecord) {
                $this->_delivery = $this->_mailing->getDelivery($this->model->getPrimaryKey(true));
            } else {
                /** @var Delivery $class */
                $class = $this->_mailing->deliveryClass;
                $this->_delivery = new $class([
                    'mailing' => $this->_mailing,
                    'model' => $this->model
                ]);
            }
        } else {
            throw new InvalidConfigException('For DeliveryWidget model must be instance of ActiveRecord.');
        }
    }

    /**
     * @return string
     * @throws InvalidParamException
     */
    public function getViewPath()
    {
        return $this->_mailing->getViewPath();
    }

    /**
     * @return string
     * @throws InvalidConfigException
     * @throws InvalidParamException
     * @throws Exception
     */
    public function run()
    {
        $this->registerAssets();
        return $this->renderInput();
    }

    /**
     * @return string
     */
    protected function getStatus()
    {
        $status = $this->_delivery->getStatus();
        return array_key_exists($status, $this->statuses) ? $this->statuses[$status] : '';
    }

    /**
     * @return string
     */
    protected function getValue()
    {
        $status = $this->getStatus();
        $total = (int) $this->_delivery->countTotal();
        if ($total === 0) {
            return "Очередь не создана - {$status}";
        }
        $stop = (int) $this->_delivery->countTotal([Delivery::STATUS_STOP]);
        $error = (int) $this->_delivery->countTotal([Delivery::STATUS_ERROR]);
        return "Выполнено {$stop} из {$total}, ошибок {$error} - {$status}";
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidParamException
     * @throws Exception
     */
    protected function registerAssets()
    {
        $view = $this->getView();
        MailingAsset::register($view);
        $url = Url::toRoute($this->_mailing->getControllerId() . '/delivery');
        $delivery = json_encode($this->_delivery->getId());
        $statuses = json_encode($this->statuses);
        $readonly = $this->readonly ? 'true' : 'false';
        $id = $this->getId();
        $view->registerJs("$('#{$id}').mailingDelivery('{$url}', {$delivery}, {$statuses}, {$readonly})", View::POS_READY);
    }

    /**
     * @return string
     * @throws InvalidParamException
     */
    protected function renderInput()
    {
        return $this->render('delivery', [
            'model' => $this->model,
            'delivery' => $this->_delivery,
            'value' => $this->getValue(),
            'id' => $this->getId(),
            'readonly' => $this->readonly,
        ]);
    }

}