<?php

use yii\web\View;
use common\components\mailing\codes\Code;
use yii\helpers\Url;

/** @var View $this  */
/** @var Code $code */
/** @var string $email */
/** @var string $home */
/** @var string $title */
/** @var string $unsubscribe */
/** @var string $configure */

?>
<p align="center">
    Это письмо сформировано автоматически.
    Пожалуйста, не отвечайте на него.
</p>
<?php if (!empty($title)) : ?>
    <p align="center">
        <a href="<?= Url::to($home) ?>" title="Источник рассылки"><?= $title ?></a>
    </p>
<?php endif; ?>
<p align="center">
    Вы можете <a href="<?= Url::to($code->applyOptions($unsubscribe, ['email' => $email])) ?>">отказаться от рассылок</a>.
</p>
<p align="center">
    Вы можете изменить свою подписку в <a href="<?= Url::to($configure) ?>" title="Настройки учетной записи">личном кабинете</a>.
</p>