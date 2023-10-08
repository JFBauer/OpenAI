<?php

namespace JFBauer\OpenAI\Services\Chat;

class ChatOptions
{
    private string $model;
    private int $temperature;
    private int $max_tokens;
    private int $top_p;
    private int $frequency_penalty;
    private int $presence_penalty;
    private array $stop;

    // Constructor
    public function __construct(array $options = []) {
        $defaults = config('openai.chat.defaultOptions');
        $combinedOptions = array_merge($defaults, $options);
        $this->fromArray($combinedOptions);
    }

    // Private methods
    private function fromArray(array $options): void {
        foreach ($options as $key => $value) {
            $setterMethod = 'set' . ucfirst($key);
            if (method_exists($this, $setterMethod)) {
                $this->{$setterMethod}($value);
            }
        }
    }

    // Setters
    public function setModel(string $model): ChatOptions {
        $this->model = $model;
        return $this;
    }

    public function setTemperature(int $temperature): ChatOptions {
        $this->temperature = $temperature;
        return $this;
    }

    public function setMaxTokens(int $maxTokens): ChatOptions {
        $this->max_tokens = $maxTokens;
        return $this;
    }

    public function setTopP(int $topP): ChatOptions {
        $this->top_p = $topP;
        return $this;
    }

    public function setFrequencyPenalty(int $frequencyPenalty): ChatOptions {
        $this->frequency_penalty = $frequencyPenalty;
        return $this;
    }

    public function setPresencePenalty(int $presencePenalty): ChatOptions {
        $this->presence_penalty = $presencePenalty;
        return $this;
    }

    public function setStop(array $stop): ChatOptions {
        $this->stop = $stop;
        return $this;
    }

    // Getters
    public function getModel(): string {
        return $this->model;
    }

    public function getTemperature(): int {
        return $this->temperature;
    }

    public function getMaxTokens(): int {
        return $this->max_tokens;
    }

    public function getTopP(): int {
        return $this->top_p;
    }

    public function getFrequencyPenalty(): int {
        return $this->frequency_penalty;
    }

    public function getPresencePenalty(): int {
        return $this->presence_penalty;
    }

    public function getStop(): array {
        return $this->stop;
    }

    // Public methods
    public function toArray(): array {
        return get_object_vars($this);
    }
}