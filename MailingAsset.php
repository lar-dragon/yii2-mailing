<?php

namespace common\components\mailing;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Class MailingAsset
 * @package common\components\mailing
 */
class MailingAsset extends AssetBundle
{
    /** @var string[] */
    public $js = [
        'tag-it.min.js',
        'mailing.js'
    ];
    /** @var array */
    public $jsOptions = ['position' => View::POS_END];
    /** @var string[] */
    public $css = [
        'jquery.tagit.css'
    ];
    /** @var array */
    public $depends = [
        'yii\jui\JuiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];


    /**
     *
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'view';
        parent::init();
    }
}