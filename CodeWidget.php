<?php

namespace common\components\mailing;

use Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException as InvalidParamException;
use yii\base\Widget;
use yii\di\Instance;
use yii\web\View;

/**
 * Виджет шордкодов.
 * @package common\components\mailing
 */
class CodeWidget extends Widget
{
    const STANDALONE = 'false';
    const TINYMCE = 'true';


    /** @var mixed */
    public $mailing = 'mailing';
    /** @var int[] */
    public $for = [];

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
     */
    public function run()
    {
        $this->registerAssets();
        return $this->renderInput();
    }

    /**
     *
     */
    protected function registerAssets()
    {
        $view = $this->getView();
        MailingAsset::register($view);
        $parser = new CodeParser([
            'mailing' => $this->_mailing
        ]);
        $parser->registerAssets($view);
        $id = $this->getId();
        foreach ($this->for as $for => $type) {
            $view->registerJs("$('#{$for}').mailingCode('{$id}', {$type})", View::POS_READY);
        }
    }

    /**
     * @return string
     */
    protected function renderInput()
    {
        return $this->render('code', [
            'id' => $this->getId(),
        ]);
    }
}