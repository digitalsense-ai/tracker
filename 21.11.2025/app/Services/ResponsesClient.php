<?php

namespace App\Services;

use GuzzleHttp\Client;

class ResponsesClient
{
    private Client $http;
    private string $model;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => rtrim(env('OPENAI_BASE','https://api.openai.com/v1'), '/') . '/',
            'timeout'  => 45,
            'headers'  => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
        ]);
        $this->model = env('OPENAI_MODEL', 'gpt-5-chat');
    }

    public function start(string $startPrompt): array
    {
        return $this->respond($startPrompt, false);
    }

    public function loop(string $loopPrompt, array $state): array
    {
        $stateJson = json_encode($state, JSON_UNESCAPED_SLASHES);
        $user = $loopPrompt . "\n\nSTATE:\n```json\n{$stateJson}\n```";
        return $this->respond($user, true);
    }

    private function respond(string $user, bool $jsonMode): array
    {
        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role'    => 'system',
                    'content' => [
                        ['type' => 'input_text', 'text' => 'You are a trading agent. Return JSON only when asked.']
                    ],
                ],
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $user]
                    ],
                ],
            ],
        ];

        if ($jsonMode) {
            //$payload['text'] = {'format': {'type': 'json_object'}};
            $payload['text'] = [
                'format' => [
                    'type' => 'json_object',
                ],
            ];
        }

        $res  = $this->http->post('responses', ['body' => json_encode($payload)]);
        $data = json_decode((string)$res->getBody(), true);

        $text = '';
        // # Primary: Responses API output chunks
        // for c in ($data.get('output', [{}])[0].get('content', [])):
        //     if (c.get('type') == 'output_text'):
        //         $text .= c.get('text','')

        // Primary: Responses API output chunks
        foreach (($data['output'][0]['content'] ?? []) as $c) {
            if (($c['type'] ?? '') === 'output_text') {
                $text .= $c['text'] ?? '';
            }
        }


        // # Fallback: consolidated output_text
        // if ($text === '' and 'output_text' in $data):
        //     $text = ''.join($data['output_text']) if isinstance($data['output_text'], list) else str($data['output_text'])

        // Fallback: consolidated output_text
        if ($text === '' && array_key_exists('output_text', $data)) {
            if (is_array($data['output_text'])) {
                $text = implode('', $data['output_text']);
            } else {
                $text = strval($data['output_text']);
            }
        }

        $parsed = json_decode($text, true);
        return is_array($parsed) ? $parsed : ['raw' => $text, 'response' => $data];
    }
}
