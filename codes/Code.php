<?php

namespace common\components\mailing\codes;

use common\components\mailing\CodeInterface;
use common\components\mailing\CodeParser;
use Html2Text\Html2Text;
use Html2Text\Html2TextException;
use JBBCode\CodeDefinition;
use JBBCode\ElementNode;
use Yii;
use yii\base\InvalidArgumentException as InvalidParamException;
use yii\base\ViewContextInterface;
use yii\helpers\Inflector;
use yii\web\View;

/**
 * Class TestCode
 * @package common\components\mailing\codes
 */
abstract class Code extends CodeDefinition implements CodeInterface, ViewContextInterface
{
    /** @var string */
    public $viewPath;
    /** @var CodeParser */
    protected $parser;
    /** @var bool */
    protected $allowClone = true;


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
     * @inheritdoc
     */
    public static function construct($tagName, $replacementText = '', $useOption = false, $parseContent = true, $nestLimit = -1, $optionValidator = array(), $bodyValidator = null)
    {
        $def = new static();
        $def->elCounter = 0;
        $def->tagName = strtolower($tagName);
        $def->replacementText = $replacementText;
        $def->useOption = $useOption;
        $def->parseContent = $parseContent;
        $def->nestLimit = $nestLimit;
        $def->optionValidator = $optionValidator;
        $def->bodyValidator = $bodyValidator;
        return $def;
    }

    /**
     * @param ElementNode $el
     * @return string
     * @throws Html2TextException
     */
    public function asHtml(ElementNode $el)
    {
        if (!$this->hasValidInputs($el)) {
            return $el->getAsBBCode();
        }
        $html = $this->parser->vars['inline']
            ? trim(preg_replace('/\s+/', ' ', Html2Text::convert($this->getReplacementText())))
            : $this->getReplacementText();
        $tokens = $this->getTokens();
        if ($this->usesOption()) {
            $options = (array) $el->getAttribute();
            if (array_key_exists($this->tagName, $options)) {
                $html = $this->applyOption('{option}', $options[$this->tagName], $html);
            }
            $val = array_reduce(array_keys($options), function ($carry, $item) use ($options) {
                if ($this->tagName === $item) {
                    return $carry;
                }
                $value = htmlentities(urldecode($options[$item]));
                return "{$carry} {$item}=\"{$value}\"";
            }, '');
            $html = $this->applyOption('{options}', $val, $html);
            foreach ($options as $key => $val) {
                $html = $this->applyOption('{' . $key . '}', $val, $html);
            }
        } else {
            $html = $this->applyOption('{option}', '', $html);
            $html = $this->applyOption('{options}', '', $html);
        }
        foreach ($tokens as $token) {
            $html = $this->applyOption($token, '', $html);
        }
        $content = $this->getContent($el);
        $html = $this->applyOption('{param}', $content, $html);
        return $html;
    }

    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser)
    {
        if (!array_key_exists('inline', $parser->vars)) {
            $parser->vars['inline'] = false;
        }
        $this->parser = $parser;
        $this->parseContent = false;
        if ($this->allowClone) {
            $this->useOption = false;
            $other = clone $this;
            $this->useOption = true;
            return [$this, $other];
        }
        return [$this];
    }

    /**
     * @param $viewFile
     * @param array $params
     * @return string
     * @throws
     */
    public function render($viewFile, $params = [])
    {
        /** @var View $view */
        $view = Yii::$app->getView();
        return $view->render($viewFile, $params, $this);
    }

    /**
     * @param View $view
     */
    public function registerAssets($view)
    {
        CodesAsset::register($view);
        $name = $this->getBuilderName();
        $view->registerJs("$.fn.mailingCode.init('{$this->tagName}', '{$name}')", View::POS_READY);
    }

    /**
     * @return array
     */
    protected function getTokens()
    {
        preg_match_all('/\{\w+\}/', $this->getReplacementText(), $tokens);
        return array_filter(isset($tokens[0]) ? array_unique($tokens[0]) : [], function ($token) {
            return !in_array($token, ['{param}', '{option}', '{options}']);
        });
    }

    /**
     * @param string $option
     * @param string $value
     * @param string $html
     * @return string
     */
    protected function applyOption($option, $value, $html)
    {
        $value = in_array($option, ['{param}', '{options}']) ? $value : htmlentities(urldecode($value));
        return str_ireplace($option, $value, $html);
    }

    /**
     * @param string $string
     * @param string[] $options
     * @return string
     */
    public function applyOptions($string, $options)
    {
        foreach ($options as $option => $value) {
            if (is_scalar($value)) {
                $string = str_ireplace("{{$option}}", $value, $string);
            }
        }
        return $string;
    }

    /**
     * @return string
     */
    protected function getBuilderName()
    {
        $name = preg_replace('/Code$/', '', basename(get_class($this)));
        return Inflector::camel2id($name);
    }
}