<?php

class Program implements JsonSerializable {
    private string $title;
    private string $startTime;
    private string $endTime;

    public function __construct(string $title, string $startTime, string $endTime) {
        $this->title = $title;
        // The orchestrator expects strict ISO 8601 formatting, these strings should already be formatted by the parser.
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function jsonSerialize(): mixed {
        return [
            'title' => $this->title,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }
}
