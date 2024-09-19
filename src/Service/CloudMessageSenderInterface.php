<?php
declare(strict_types=1);

namespace Src\Service;

interface CloudMessageSenderInterface
{
    public function sendToOne(string $token, array $message);

    public function sendToMany(array $tokens, array $message);

    public function sendToTopic(string $topicName, array $message);
}