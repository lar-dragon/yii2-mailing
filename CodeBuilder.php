<?php

namespace common\components\mailing;

use Exception;
use JBBCode\CodeDefinition;
use JBBCode\InputValidator;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\web\View;

/**
 * Class CodeBuilder
 * @package common\components\mailing
 */
class CodeBuilder extends BaseObject implements CodeInterface
{
    use IsTrait;

    /** @var string */
    public $tagName;
    /** @var string */
    public $replacementText;
    /** @var bool */
    public $useOption = false;
    /** @var bool */
    public $parseContent = true;
    /** @var int */
    public $nestLimit = -1;
    /** @var InputValidator[] */
    public $optionValidator = [];
    /** @var InputValidator */
    public $bodyValidator;
    /** @var string */
    public $codeClass = 'JBBCode\CodeDefinition';
    /** @var array */
    public $codeConfig = [];


    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!$this->is($this->codeClass, 'JBBCode\CodeDefinition')) {
            throw new InvalidConfigException('Class name in codeClass may implement JBBCode\CodeDefinition');
        }
    }

    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser)
    {
        /** @var CodeDefinition $class */
        $class = $this->codeClass;
        $result = $class::construct(
            $this->tagName,
            $this->replacementText,
            $this->useOption,
            $this->parseContent,
            $this->nestLimit,
            $this->optionValidator,
            $this->bodyValidator
        );
        foreach ($this->codeConfig as $key => $value) {
            if (property_exists($result, $key)) {
                $result->$key = $value;
            }
        }
        if ($result instanceof CodeInterface) {
            $parser->addAssetsRegister([$result, 'registerAssets']);
            return $result->getDefinitions($parser);
        }
        $parser->addAssetsRegister([$this, 'registerAssets']);
        return [$result];
    }

    /**
     * @param View $view
     */
    public function registerAssets($view)
    {
        $json = json_encode([
            'pattern' => $this->replacementText,
            'option' => $this->useOption,
            'param' => $this->parseContent,
        ]);
        $view->registerJs("$.fn.mailingCode.codes.set('{$this->tagName}', $.fn.mailingCode.create({$json}))", View::POS_READY);
        $view->registerJs("$.fn.mailingCode.init('{$this->tagName}', '{$this->tagName}')", View::POS_READY);
    }
}