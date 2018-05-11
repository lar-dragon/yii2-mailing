<?php

namespace common\components\mailing\codes;

use common\components\mailing\CodeParser;
use Exception;
use JBBCode\CodeDefinition;
use yii\web\View;

/**
 * Class CountCode
 * @package common\components\mailing\codes
 */
class CountCode extends Code
{
    /** @var View */
    protected $view;
    /** @var CountableInterface[] */
    protected $counters = [];


    /**
     * @param View $view
     */
    public function registerAssets($view)
    {
        $this->view = $view;
        parent::registerAssets($view);
    }

    /**
     *
     */
    public function onReady()
    {
        foreach ($this->parser->getCodeDefinitions() as $definition) {
            if ($definition instanceof CountableInterface) {
                foreach ($definition->getCounters() as $counter => $title) {
                    $this->counters[$counter] = $definition;
                    $this->view->registerJs("$.fn.mailingCode.tags.get('{$this->tagName}').counter('{$counter}', '{$title}')", View::POS_READY);
                }
            }
        }
    }

    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser)
    {
        $parser->on(CodeParser::EVENT_READY, [$this, 'onReady']);
        $this->replacementText = '<p>{option}</p>';
        $this->parseContent = false;
        return parent::getDefinitions($parser);
    }

    /**
     * @param string $option
     * @param string $value
     * @param string $html
     * @return string
     * @throws Exception
     */
    protected function applyOption($option, $value, $html)
    {
        if ($option === '{option}') {
            $value = array_key_exists($value, $this->counters)
                ? $this->counters[$value]->getCount($value)
                : 0;
            if ($value < 1) {
                throw new Exception("Счетчик {$value} не использовался. Рассылка не состоятельна.");
            }
        }
        return parent::applyOption($option, $value, $html);
    }
}