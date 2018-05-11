<?php

use common\components\mailing\Delivery;
use yii\db\ActiveRecord;
use yii\web\View;

/** @var View $this */
/** @var ActiveRecord $model */
/** @var string $id */
/** @var string $value */
/** @var Delivery $delivery */
/** @var bool $readonly */

$status = $delivery->getStatus();
?>
<?php if (!empty($id)) : ?>
    <span id="<?= $id ?>">
<?php endif; ?>
    <?php if ($model->isNewRecord) : ?>
        Не сохранено
    <?php else : ?>
        <?= $value ?>
        <?php if (!$readonly) : ?>
            <?php if ($status === Delivery::STATUS_ERROR || $status === Delivery::STATUS_STOP) : ?>
                <a href="#start" title="Запустить" aria-label="Запустить" data-pjax="0"><span  class="glyphicon glyphicon-play"></span></a>
            <?php endif; ?>
            <?php if ($status === Delivery::STATUS_ERROR || $status === Delivery::STATUS_PAUSE) : ?>
                <a href="#resume" title="Продолжить" aria-label="Продолжить" data-pjax="0"><span class="glyphicon glyphicon-play"></span></a>
            <?php endif; ?>
            <?php if ($status === Delivery::STATUS_PLAY || $status === Delivery::STATUS_PAUSE) : ?>
                <a href="#stop" title="Остановить" aria-label="Остановить" data-pjax="0"><span class="glyphicon glyphicon-stop"></span></a>
            <?php endif; ?>
            <?php if ($status === Delivery::STATUS_PLAY) : ?>
                <a href="#pause" title="Приостановить" aria-label="Приостановить" data-pjax="0"><span class="glyphicon glyphicon-pause"></span></a>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php if (!empty($id)) : ?>
    </span>
<?php endif; ?>