<?php

namespace App\Support;

class AiReplyFormatter
{
    public static function segments(string $reply, string $channel): array
    {
        $reply = trim($reply);

        if ($reply === '' || $channel === 'Gmail') {
            return $reply === '' ? [] : [$reply];
        }

        $segments = collect(preg_split('/\s*\|\|\|\s*/', $reply) ?: [])
            ->map(fn ($segment) => trim($segment))
            ->filter()
            ->take(2)
            ->values()
            ->all();

        if (count($segments) === 1 && mb_strlen($segments[0]) > 280) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $segments[0]) ?: [$segments[0]];
            $first = '';
            $second = '';

            foreach ($sentences as $sentence) {
                if ($first === '' || mb_strlen($first.' '.$sentence) <= 180) {
                    $first = trim($first.' '.$sentence);
                } else {
                    $second = trim($second.' '.$sentence);
                }
            }

            if ($second !== '') {
                $segments = [$first, $second];
            }
        }

        return $segments;
    }
}
