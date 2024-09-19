<?php
declare(strict_types=1);

namespace Src\Service;

interface TopicManagerInterface
{
    public function subscribeTo(string $topicName, array $tokens);
}