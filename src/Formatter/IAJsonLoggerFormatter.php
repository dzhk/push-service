<?php
namespace Src\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class IAJsonLoggerFormatter extends JsonFormatter
{
    public function format(array|\Monolog\LogRecord $record): string
    {
        if ($record instanceof LogRecord) {
            $record = $record->toArray();
        }
        $normalized = $this->normalize($record);

        $normalized['message'] = $normalized['message'] ?? '';
        $normalized['context'] = $normalized['context'] ?? [];
        $normalized['extra'] = $normalized['extra'] ?? [];

        $entry = array_merge(
            $normalized,
            $normalized['context'],
            $normalized['extra']
        );

        unset($entry['context'], $entry['extra'], $entry['datetime'], $entry['channel']);

        $entry['msg'] = $entry['message'];
        unset($entry['message']);

        if (isset($entry['timestamp'])) {
            $entry['ts'] = $entry['timestamp'];
            unset($entry['timestamp']);
        }

        if (isset($entry['level_name'])) {
            $entry['level'] = strtolower($entry['level_name']);
            unset($entry['level_name']);
        }

        return $this->toJson($entry) . ($this->appendNewline ? "\n" : '');
    }
}