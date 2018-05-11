<?php

namespace common\components\mailing;

use JBBCode\CodeDefinition;
use JBBCode\CodeDefinitionSet;
use JBBCode\Parser;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\mail\MessageInterface;
use yii\web\View;

/**
 * Class CodeParser
 * @package common\components\mailing
 */
class CodeParser extends Component implements CodeDefinitionSet
{
    use IsTrait;

    const EVENT_BEFORE = 'before';
    const EVENT_AFTER  = 'after';
    const EVENT_READY  = 'ready';
    const EVENT_DESTRUCT  = 'destruct';

    /** @var Mailing */
    public $mailing;
    /** @var Target */
    public $target;
    /** @var MessageInterface */
    public $mail;
    /** @var array */
    public $vars = [];

    /** @var callable[] */
    protected $_assetsRegister = [];
    /** @var CodeDefinition[] */
    protected $_definitions = [];


    public function __destruct()
    {
        $this->trigger(self::EVENT_DESTRUCT);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if ($this->mailing === null && $this->target instanceof Target) {
            $this->mailing = $this->target->delivery->mailing;
        }
        foreach ($this->mailing->codes as $tagName => $code) {
            /** @var CodeBuilder $instance */
            if (is_string($code) && $this->is($code, 'JBBCode\CodeDefinition')) {
                $instance = new CodeBuilder([
                    'codeClass' => $code,
                ]);
            } else if (is_array($code) && array_key_exists('class', $code) && is_string($code['class']) && $this->is($code['class'], 'JBBCode\CodeDefinition')) {
                $class = $code['class'];
                unset($code['class']);
                $instance = $instance = new CodeBuilder([
                    'codeClass' => $class,
                    'codeConfig' => $code
                ]);
            } else {
                $instance = Instance::ensure($code, 'common\components\mailing\CodeBuilder');
            }
            if (empty($instance->tagName)) {
                $instance->tagName = $tagName;
            }
            if (empty($instance->replacementText)) {
                $instance->replacementText = '{param}';
            }
            foreach ($instance->getDefinitions($this) as $definition) {
                $this->_definitions[] = $definition;
            }
        }
    }

    /**
     * @return CodeDefinition[]
     */
    public function getCodeDefinitions()
    {
        return $this->_definitions;
    }

    /**
     * @param string $string
     * @param array $vars
     * @return string
     */
    public function parse($string, $vars = [])
    {
        $this->vars = array_merge($this->vars, $vars);
        $parser = new Parser();
        $parser->addCodeDefinitionSet($this);
        $this->trigger(self::EVENT_BEFORE);
        $parser->parse($string);
        $this->trigger(self::EVENT_AFTER);
        return $parser->getAsHTML();
    }

    /**
     * @param callable $callback
     */
    public function addAssetsRegister($callback)
    {
        if (is_callable($callback)) {
            $this->_assetsRegister[] = $callback;
        }
    }

    /**
     * @param View $view
     */
    public function registerAssets($view)
    {
        foreach ($this->_assetsRegister as $callback) {
            $callback($view);
        }
        $this->trigger(self::EVENT_READY);
    }
}