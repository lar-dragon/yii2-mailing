<?php

namespace common\components\mailing;

use Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException as InvalidParamException;
use yii\base\Widget;
use yii\di\Instance;
use yii\helpers\Url;
use yii\web\View;

class MailingWidget extends Widget
{
    /** @var mixed */
    public $mailing = 'mailing';
    /** @var string */
    public $log = '#log';
    /** @var string[] */
    public $statuses = [
        'pause' => 'Ожидает',
        'play' => 'Выполняется',
        'step' => 'Шаг',
    ];

    /** @var Mailing */
    protected $_mailing;


    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function init()
    {
        parent::init();
        $this->_mailing = Instance::ensure($this->mailing, Mailing::className());
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
     * @throws InvalidConfigException
     * @throws InvalidParamException
     * @throws Exception
     */
    protected function registerAssets()
    {
        $view = $this->getView();
        MailingAsset::register($view);
        $id = $this->getId();
        $url = Url::toRoute($this->_mailing->getControllerId() . '/mailing');
        $json = json_encode($this->statuses);
        $view->registerJs("$('#{$id}').mailing('{$url}', '{$this->log}', $json)", View::POS_READY);
    }

    /**
     * @return string
     * @throws InvalidParamException
     */
    protected function renderInput()
    {
        return $this->render('mailing', [
            'id' => $this->getId(),
            'status' => $this->statuses['pause']
        ]);
    }
}