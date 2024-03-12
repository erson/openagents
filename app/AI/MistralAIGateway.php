<?php

namespace App\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;

class MistralAIGateway
{
    public function chat()
    {
        return new MistralAIChat();
    }

    public function inference($messages)
    {
        // Your API endpoint
        $url = 'https://api.mistral.ai/v1/chat/completions';

        // Prepare the data payload
        $data = [
            'model' => 'mistral-large-latest',
            'messages' => $messages,
            'max_tokens' => 2000,
            'temperature' => 0.5,
            'top_p' => 1,
        ];

        // Make the HTTP POST request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.env('MISTRAL_API_KEY'),
        ])->post($url, $data);

        // Check if the request was successful
        if ($response->successful()) {
            // Return the response body
            return $response->json();
        } else {
            // Handle the error (you can customize this part based on your needs)
            return [
                'error' => 'Failed to make inference',
                'details' => $response->json(),
            ];
        }
    }
}

class MistralAIChat
{
    public function createStreamed($params)
    {
        $url = 'https://api.mistral.ai/v1/chat/completions';
        $apiKey = env('MISTRAL_API_KEY');
        $model = $params['model'];
        $messages = $params['messages'];
        $maxTokens = $params['max_tokens'];
        $streamFunction = $params['stream_function'];
        $temperature = $params['temperature'] ?? 0.5;
        $topP = $params['top_p'] ?? 1;

        $client = new Client();

        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'top_p' => $topP,
            'stream' => true, // Ensure this is true for streaming
        ];

        try {
            $response = $client->post($url, [
                'json' => $data,
                'stream' => true, // Important for streaming
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                ],
            ]);

            $stream = $response->getBody();
            $content = '';
            foreach ($this->readStream($stream) as $responseLine) {
                if (isset($responseLine['choices'][0]['delta']['content'])) {
                    $content .= $responseLine['choices'][0]['delta']['content'];
                    $streamFunction($responseLine);
                    // Here, you could also broadcast or handle each token/message part as it arrives
                }
            }

            return $content;
        } catch (RequestException $e) {
            // Handle exception or error
            dd('error', $e->getMessage());

            return 'Error: '.$e->getMessage();
        }
    }

    private function readStream($stream)
    {
        while (! $stream->eof()) {
            $line = $this->readLine($stream);
            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $data = trim(substr($line, 5)); // Skip the 'data:' part
            if ($data === '[DONE]') {
                break;
            }

            $response = json_decode($data, true);
            if ($response) {
                yield $response;
            }
        }
    }

    private function readLine($stream)
    {
        $line = '';
        while (! $stream->eof()) {
            $char = $stream->read(1);
            if ($char === "\n") {
                break;
            }
            $line .= $char;
        }

        return $line;
    }
}