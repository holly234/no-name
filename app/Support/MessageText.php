<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class MessageText
{
    public static function linkify(string $text): HtmlString
    {
        $pattern = '~https?://[^\s<]+~i';
        $offset = 0;
        $html = '';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$url, $position]) {
            $html .= e(substr($text, $offset, $position - $offset));

            [$cleanUrl, $trailing] = self::splitTrailingPunctuation($url);
            $escapedUrl = e($cleanUrl);

            $html .= '<a href="'.$escapedUrl.'" target="_blank" rel="noopener noreferrer" class="text-[#7cc4ff] underline decoration-[#7cc4ff]/40 underline-offset-2 hover:text-[#a7d8ff]">'.$escapedUrl.'</a>';
            $html .= e($trailing);

            $offset = $position + strlen($url);
        }

        $html .= e(substr($text, $offset));

        return new HtmlString($html);
    }

    private static function splitTrailingPunctuation(string $url): array
    {
        $trailing = '';

        while ($url !== '' && preg_match('/[.,!?;:\]\)]$/', $url)) {
            $trailing = substr($url, -1).$trailing;
            $url = substr($url, 0, -1);
        }

        return [$url, $trailing];
    }
}
