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
            'base_uri' => rtrim(env('OPENAI_BASE','https://api.openai.com/v1'), '/').'/' ,
            'timeout'  => 45,
            'headers'  => [
                'Authorization' => 'Bearer '.env('OPENAI_API_KEY'),
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
                    'content' => [['type'=>'text','text'=>'You are a trading agent. Return JSON only when asked.']],
                ],
                [
                    'role'    => 'user',
                    'content' => [['type'=>'text','text'=>$user]],
                ],
            ],
        ];

        if ($jsonMode) {
            $payload['text'] = ['format' => ['type' => 'json_object']];
        }

        $res  = $this->http->post('responses', ['body' => json_encode($payload)]);
        $data = json_decode((string)$res->getBody(), true);

        $text = '';
        foreach (($data['output'][0]['content'] ?? []) as $c) {
            if (($c['type'] ?? '') === 'output_text') { $text .= $c['text']; }
        }

        $parsed = json_decode($text, true);
        return is_array($parsed) ? $parsed : ['raw' => $text, 'response' => $data];
    }
}
