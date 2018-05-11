<?php

namespace common\components\mailing\codes;

use common\components\mailing\CodeParser;
use JBBCode\CodeDefinition;

/**
 * Шордкод выводящий имя пользователя которому отправляется письмо.
 * @package common\components\mailing\codes
 */
class DateUptoCode extends Code
{
    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser)
    {
        $this->replacementText = '<p>{option}</p>';
        $this->parseContent = false;
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
        if ($option === '{option}') {
            $value = date('d.m.Y', $this->parser->target->delivery->getNextDate());
        }
        return parent::applyOption($option, $value, $html);
    }
}