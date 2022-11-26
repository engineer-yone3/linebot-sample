<?php

namespace App\Services\Event;

use App\Services\Bot\LineApiServiceInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\ImageMessage;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LineImageEvent {
    public function __construct(
        private LineApiServiceInterface $service
    )
    {

    }

    public function execute(ImageMessage $event, LINEBot $bot): void
    {
        $contentProvider = $event->getContentProvider();

        logger()->debug('[event data]');
        logger()->debug(print_r($event, true));
        if ($contentProvider->isExternal()) {
            logger()->debug('外部ファイル');
            $imageMessage = new ImageMessageBuilder(
                $contentProvider->getOriginalContentUrl(),
                $contentProvider->getPreviewImageUrl()
            );
            $textMessage = new TextMessageBuilder('送られた画像はこちらです');
            $builder = new MultiMessageBuilder();
            $builder->add($textMessage);
            $builder->add($imageMessage);
            $this->service->replyMessage($bot, $event->getReplyToken(), $builder);
        } elseif ($contentProvider->isLine()) {
            logger()->debug('Lineファイル');
            $messageId = $event->getMessageId();
            $response = $bot->getMessageContent($messageId);
            if ($response->isSucceeded()) {
                $fileName = Str::random(255);
                Storage::disk('public')->put($fileName, $response->getRawBody());
                $url = Storage::disk('public')->url($fileName);

                $imageMessage = new ImageMessageBuilder($url,$url);
                $textMessage = new TextMessageBuilder('送られた画像はこちらです');
                $builder = new MultiMessageBuilder();
                $builder->add($textMessage);
                $builder->add($imageMessage);
                $this->service->replyMessage($bot, $event->getReplyToken(), $builder);
            }
        }
    }
}