<?php

namespace common\components\mailing;

use JBBCode\CodeDefinition;
use yii\web\View;

/**
 * Interface CodeInterface
 * @package common\components\mailing
 */
interface CodeInterface
{
    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser);

    /**
     * @param View $view
     * @return void
     */
    public function registerAssets($view);
}