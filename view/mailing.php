<?php

use yii\web\View;

/** @var View $this */
/** @var string $id */
/** @var string $status */

?>
<span id="<?= $id ?>">
    <span class="status"><?= $status ?></span>
    <a href="#clear" title="Очистить лог" aria-label="Очистить лог" data-pjax="0"><span class="glyphicon glyphicon-trash"></span></a>
    <a href="#pause" title="Приостановить утилизацию" aria-label="Приостановить утилизацию" data-pjax="0"><span class="glyphicon glyphicon-pause"></span></a>
    <a href="#play" title="Утилизировать очередь" aria-label="Утилизировать очередь" data-pjax="0"><span class="glyphicon glyphicon-play"></span></a>
    <a href="#step" title="Выполнить один элемент" aria-label="Выполнить один элемент" data-pjax="0"><span class="glyphicon glyphicon-step-forward"></span></a>
</span>
