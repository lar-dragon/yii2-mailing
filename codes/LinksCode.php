<?php

namespace common\components\mailing\codes;

use common\components\mailing\CodeParser;
use Html2Text\Html2Text;
use Html2Text\Html2TextException;
use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

/**
 * Class LinksCode
 * @package common\components\mailing\codes
 */
class LinksCode extends Code
{
    /** @var string */
    public $title = '';
    /** @var string */
    public $unsubscribe = '';
    /** @var string */
    public $configure = '';


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
        $html = $this->render('links', [
            'code' => $this,
            'email' => $this->parser->target->email,
            'title' => $this->title,
            'home' => $this->parser->mailing->getHome(),
            'unsubscribe' => $this->parser->mailing->getHome() . $this->unsubscribe,
            'configure' => $this->parser->mailing->getHome() . $this->configure,
        ]);
        return $this->parser->vars['inline'] ? trim(preg_replace('/\s+/', ' ', Html2Text::convert($html))) : $html;
    }

    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser)
    {
        $this->replacementText = '<p>Блок ссылок</p>';
        $this->parseContent = false;
        return parent::getDefinitions($parser);
    }
}