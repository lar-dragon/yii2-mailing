<?php

namespace common\components\mailing\codes;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Class MailingAsset
 * @package common\components\mailing
 */
class CodesAsset extends AssetBundle
{
    /** @var string[] */
    public $js = [
        'test.js',
        'date-from.js',
        'date-upto.js',
        'count.js',
        'list.js',
        'links.js',
    ];
    /** @var array */
    public $jsOptions = ['position' => View::POS_END];
    /** @var array */
    public $depends = ['common\components\mailing\MailingAsset'];


    /**
     *
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'assets';
        parent::init();
    }
}