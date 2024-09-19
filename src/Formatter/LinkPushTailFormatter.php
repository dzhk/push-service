<?php

namespace Src\Formatter;

use Src\Encoder\UrlOpenSslEncoder;

class LinkPushTailFormatter
{
    /**
     * @var UrlOpenSslEncoder
     */
    private $encoder;

    public function __construct($encoder)
    {

        $this->encoder = $encoder;
    }

    public function format(array $data): string
    {
        $tail = $this->encoder->encode(sprintf('news_time=%s&news_id=%s&rotation=%s', time(), 0, ''));
        $checkHash = $this->encoder->getHash($tail);
        $tail = rawurlencode($tail);
        return sprintf('d=%s&m=%s&size=0&zone=push&pos=1', $tail, $checkHash);
    }
}