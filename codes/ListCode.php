<?php

namespace common\components\mailing\codes;

use common\components\mailing\CodeParser;
use Exception;
use Html2Text\Html2Text;
use Html2Text\Html2TextException;
use Imagine\Image\Box;
use Imagine\Image\ManipulatorInterface;
use JBBCode\CodeDefinition;
use JBBCode\ElementNode;
use yii\helpers\Url;
use yii\imagine\Image;
use yii\web\View;

/**
 * Class ListCode
 * @package common\components\mailing\codes
 */
abstract class ListCode extends Code implements CountableInterface
{
    /** @var string */
    public $itemURL = '';
    /** @var string */
    public $itemsURL = '';
    /** @var array[] */
    protected static $counters = [
        'news' => ['count' => 0, 'title' => 'Новости'],
    ];
    /** @var View */
    protected $view;
    /** @var string[] */
    protected $tmp = [];


    /**
     *
     */
    public function onDestruct()
    {
        foreach ($this->tmp as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public function crop($path)
    {
        $id = count($this->tmp);
        $this->tmp[$id] = tempnam(sys_get_temp_dir(), 'crp');
        Image::getImagine()
            ->open($path)
            ->copy()
            ->thumbnail(new Box(270, 160), ManipulatorInterface::THUMBNAIL_INSET)
            ->save($this->tmp[$id], ['format' => 'jpeg', 'quality' => 80]);
        return $this->tmp[$id];
    }

    /**
     * @param View $view
     */
    public function registerAssets($view)
    {
        $this->view = $view;
        parent::registerAssets($view);
    }

    /**
     * @param ElementNode $el
     * @return string
     * @throws Html2TextException
     * @throws Exception
     */
    public function asHtml(ElementNode $el)
    {
        if (!$this->hasValidInputs($el)) {
            return $el->getAsBBCode();
        }
        $options = (array) $el->getAttribute();
        if (array_key_exists($this->tagName, $options)) {
            $counter = $options[$this->tagName];
            $items = $this->getItems($counter, $options);
            if (empty($items)) {
                throw new Exception('Счетчик {$counter} не использовался. Рассылка не состоятельна.');
            }
            self::$counters[$counter]['count'] += count($items);
            $html = $this->render($options[$this->tagName], [
                'items' => $items,
                'mail' => $this->parser->mail,
                'code' => $this,
                'itemsURL' => $this->parser->mailing->getHome() . $this->itemsURL,
                'itemURL' => $this->parser->mailing->getHome() . $this->itemURL,
            ]);
            return $this->parser->vars['inline'] ? trim(preg_replace('/\s+/', ' ', Html2Text::convert($html))) : $html;
        }
        return '';
    }

    /**
     * @return string[]
     */
    public function getCounters()
    {
        return array_map(function ($counter) {
            return $counter['title'];
        }, self::$counters);
    }

    /**
     * @param string $counter
     * @return int
     */
    public function getCount($counter)
    {
        return array_key_exists($counter, self::$counters) ? self::$counters[$counter]['count'] : 0;
    }

    /**
     * @param CodeParser $parser
     * @return CodeDefinition[]
     */
    public function getDefinitions($parser)
    {
        $parser->on(CodeParser::EVENT_READY, [$this, 'onReady']);
        $parser->on(CodeParser::EVENT_DESTRUCT, [$this, 'onDestruct']);
        $this->replacementText = '<p>{option}</p>';
        return parent::getDefinitions($parser);
    }

    /**
     *
     */
    public function onReady()
    {
        $url = Url::toRoute($this->parser->mailing->getControllerId() . '/list');
        foreach (self::$counters as $name => $counter) {
            $this->view->registerJs("$.fn.mailingCode.tags.get('{$this->tagName}').list('{$name}', '{$counter['title']}', '{$url}')", View::POS_READY);
        }
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
            $value = array_key_exists($value, self::$counters) ? self::$counters[$value]['title'] : $value;
        }
        return parent::applyOption($option, $value, $html);
    }

    /**
     * @param string $name
     * @param string[] $options
     * @return array
     */
    protected function getItems($name, $options)
    {
        switch ($name) {
            case 'news': return $this->getNews($options);
            default: return [];
        }
    }

    /**
     * @param string[] $options.
     * @return array[]
     */
    abstract protected function getNews($options);
}