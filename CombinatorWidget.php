<?php

namespace common\components\mailing;

use Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException as InvalidParamException;
use yii\di\Instance;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\InputWidget;

/**
 * Class CombinatorWidget
 * @package common\components\mailing
 */
class CombinatorWidget extends InputWidget
{
    use IsTrait;

    /** @var mixed */
    public $mailing = 'mailing';
    /** @var int */
    public $limit = Delivery::LIMIT;
    /** @var bool */
    public $readonly = false;

    /** @var Mailing */
    protected $_mailing;


    /**
     * @inheritdoc
     * @throws InvalidConfigException
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
        if ($this->readonly) {
            return $this->getValue();
        }
        $this->setId(Html::getInputId($this->model, $this->attribute));
        $this->registerAssets();
        return $this->renderInput();
    }

    /**
     * @return string
     */
    protected function getValue()
    {
        $serializer = $this->_mailing->getQueue()->serializer;
        $text = $this->model->{$this->attribute};
        $combinator = empty($text)
            ? new Combinator()
            : $serializer->unserialize($text);
        $tags = array_filter($this->_mailing->getTags(), function ($tag) use ($combinator) {
            return in_array($tag, $combinator->tags, true);
        }, ARRAY_FILTER_USE_KEY);
        return implode(';', array_unique(array_merge($tags, $combinator->emails)));
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
        $id = $this->getId() . '_tag';
        $url = Url::toRoute($this->_mailing->getControllerId() . '/combinator');
        $json = json_encode(array_values($this->_mailing->getTags()));
        $view->registerJs("$('#{$id}').mailingCombinator('{$url}', {$json}, {$this->limit})", View::POS_READY);
    }

    /**
     * @return string
     */
    protected function renderInput()
    {
        return Html::textInput('', $this->getValue(), ['id' => $this->getId() . '_tag', 'data-for' => '#' . $this->getId(), 'class' => 'form-control'])
             . Html::activeHiddenInput($this->model, $this->attribute, ['id' => $this->getId()]);
    }
}