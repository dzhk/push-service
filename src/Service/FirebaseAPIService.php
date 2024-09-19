<?php
declare(strict_types=1);

namespace Src\Service;

use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;


class FirebaseAPIService implements TopicManagerInterface, CloudMessageSenderInterface
{
    private $firebase;

    public function __construct(array $settings)
    {
        $this->firebase = (new Factory())
            ->withServiceAccount($settings);
    }

    public function subscribeTo(string $topicName, array $tokens)
    {
        return $this->firebase->createMessaging()->subscribeToTopic($topicName, $tokens);
    }

    public function unsubscribeFrom(string $topicName, array $tokens)
    {
        return $this->firebase->createMessaging()->unsubscribeFromTopic($topicName, $tokens);
    }

    public function unsubscribeFromAll(array $tokens)
    {
        return $this->firebase->createMessaging()->unsubscribeFromAllTopics($tokens);
    }

    public function sendToOne(string $token, array $message)
    {
        return $this->sendToMany([$token], $message);
    }

    public function sendToMany(array $tokens, array $message, $validateOnly = false)
    {
        return $this->firebase->createMessaging()->sendMulticast(CloudMessage::fromArray($message), $tokens, $validateOnly);
    }

    public function sendMulticast($cloudMessage, $tokens, $validateOnly = false)
    {
        return $this->firebase->createMessaging()->sendMulticast($cloudMessage, $tokens, $validateOnly);
    }

    public function sendAll($messages)
    {
        return $this->firebase->createMessaging()->sendAll($messages, true);

    }

    public function sendToTopic(string $topicName, array $message)
    {
        $topicNameArr = ['topic' => $topicName];

        return $this->firebase->createMessaging()->send(array_merge($topicNameArr, $message));
    }

    /**
     * @throws MessagingException
     * @throws FirebaseException
     */
    public function validateTokens(array $tokens): array
    {
        return $this->firebase->createMessaging()->validateRegistrationTokens($tokens);
    }
}