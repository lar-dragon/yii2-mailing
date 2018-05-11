<?php

use yii\web\View;

/** @var View $this */
/** @var string $id */

?>
<div id="<?= $id ?>" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Добавление шорткода</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <textarea id="<?= $id ?>-result" class="form-control" readonly></textarea>
                    <p class="help-block"></p>
                </div>
                <div class="form-group">
                    <label for="<?= $id ?>-option">Параметр</label>
                    <input id="<?= $id ?>-option" class="form-control" type="text">
                </div>
                <fieldset id="<?= $id ?>-options">

                </fieldset>
                <div class="form-group">
                    <label for="<?= $id ?>-param">Содержимое</label>
                    <textarea id="<?= $id ?>-param" class="form-control result" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                <button id="<?= $id ?>-submit" type="button" class="btn btn-primary">Добавить</button>
            </div>
        </div>
    </div>
</div>