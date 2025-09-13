<?php
// Functions/summarizer.php

require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../Config/config.php'; // define OPENAI_API_KEY in here

use OpenAI;

function Summarizer(string $text, string $apiKey): string
{
   
    try {
        $client = OpenAI::client($apiKey);

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Summarize this text in max 3 sentences:'],
                ['role' => 'user', 'content' => $text]
            ],
            'max_tokens' => 200,
        ]);


        return trim($response->choices[0]->message->content ?? "Summary unavailable.");
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}
