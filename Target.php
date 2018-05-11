<?php

namespace common\components\mailing;

use Html2Text\Html2Text;
use Html2Text\Html2TextException;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Class Target
 * @package common\components\mailing
 */
class Target extends BaseObject implements JobInterface
{
    /** @var Delivery */
    public $delivery;
    /** @var string */
    public $email;
    /** @var string */
    public $job;
    /** @var int */
    public $status = Delivery::STATUS_PLAY;


    /**
     * @param Queue $queue
     * @throws Html2TextException
     */
    public function execute($queue)
    {
        $mail = $this->delivery->mailing->getMailer()->compose();
        $parser = new CodeParser([
            'target' => $this,
            'mail' => $mail,
        ]);
        $body = $parser->parse($this->delivery->getBody());
        $subject = $parser->parse($this->delivery->getSubject(), ['inline' => true]);
        $mail->setSubject($subject);
        $mail->setHtmlBody($body);
        $mail->setTextBody(Html2Text::convert($body));
        $mail->setTo($this->email);
        $mail->setFrom($this->delivery->mailing->from);
        if ($mail->send()) {
            $this->status = Delivery::STATUS_STOP;
            $this->job = null;
        }
    }
}