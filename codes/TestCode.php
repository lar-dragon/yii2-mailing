<?php

namespace common\components\mailing\codes;

use common\components\mailing\CodeParser;
use JBBCode\CodeDefinition;

/**
 * Class TestCode
 * @package common\components\mailing\codes
 */
class TestCode extends Code
{
    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser)
    {
        $this->replacementText = '<{option}{options}>{param}</{option}>';
        return parent::getDefinitions($parser);
    }

    /**
     * @param string $option
     * @param string $value
     * @param string $html
     * @return string
     */
    protected function applyOption($option, $value, $html)
    {
        if ($option === '{option}' && empty($value)) {
            $value = 'div';
        }
        return parent::applyOption($option, $value, $html);
    }
}