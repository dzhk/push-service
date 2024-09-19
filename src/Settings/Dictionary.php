<?php
declare(strict_types=1);

namespace Src\Settings;

use OpenTelemetry\SDK\Common\Configuration\KnownValues;

interface Dictionary
{
    public const GRPC = KnownValues::VALUE_GRPC;
    public const HTTP_PROTOBUF = KnownValues::VALUE_HTTP_PROTOBUF;
    public const HTTP_JSON = KnownValues::VALUE_HTTP_JSON;
    public const HTTP_NDJSON = KnownValues::VALUE_HTTP_NDJSON;
}