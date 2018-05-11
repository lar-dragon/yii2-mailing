<?php
return [
    'bootstrap' => [
        'mailing'
    ],
    'components' => [
        'mailing' => [
            'class' => 'common\components\mailing\Mailing',
            'deliveryClass' => 'common\components\mailing\Delivery',
            'from' => ['root@localhost' => 'Автоматическая рассылка'],
            'codes' => [
                'test' => 'common\components\mailing\codes\TestCode',
                'date-from' => 'common\components\mailing\codes\DateFromCode',
                'date-upto' => 'common\components\mailing\codes\DateUptoCode',
                'count' => 'common\components\mailing\codes\CountCode',
                'list' => [
                    'class' => 'common\components\mailing\codes\ListCode',
                    'itemURL' => '/news/{ID}',
                    'itemsURL' => '/news',
                ],
                'links' => [
                    'class' => 'common\components\mailing\codes\LinksCode',
                    'title' => 'Тестовый стенд',
                    'unsubscribe' => '/unsubscribe?email={EMAIL}',
                    'configure' => '/configure',
                ],
            ],
            'queue' => [
                'class' => 'common\components\mailing\Queue',
                'db' => 'db',
                'tableName' => 'queue',
                'channel' => 'mailing',
                'mutex' => 'yii\mutex\OracleMutex',
                'attempts' => 3,
                'ttr' => 300,
            ],
        ],
    ],
];
