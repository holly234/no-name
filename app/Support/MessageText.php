<?php

namespace App\Support;

use Illuminate\Support\Arr;
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

    public static function gmailHtml(string $html, iterable $attachments = []): HtmlString
    {
        if ($html === '') {
            return new HtmlString('');
        }

        $html = self::replaceCidImages($html, $attachments);
        $html = self::sanitizeHtml($html);

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

    private static function replaceCidImages(string $html, iterable $attachments): string
    {
        $cidMap = [];

        foreach ($attachments as $attachment) {
            $metadata = (array) ($attachment->metadata ?? []);
            $contentId = strtolower(trim((string) ($metadata['content_id'] ?? ''), '<>'));

            if ($contentId === '') {
                continue;
            }

            $cidMap['cid:'.$contentId] = route('dashboard.attachments.download', [
                'attachment' => $attachment,
                'inline' => 1,
            ]);
        }

        if ($cidMap === []) {
            return $html;
        }

        return preg_replace_callback('/(<img\b[^>]*\bsrc=(["\']))cid:([^"\']+)\2/i', function (array $matches) use ($cidMap): string {
            $cid = 'cid:'.strtolower(trim(html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'), '<>'));

            return isset($cidMap[$cid])
                ? $matches[1].'src='.$matches[2].e($cidMap[$cid]).$matches[2]
                : $matches[0];
        }, $html) ?? $html;
    }

    private static function sanitizeHtml(string $html): string
    {
        $allowedTags = [
            'a', 'abbr', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'i', 'img',
            'li', 'ol', 'p', 'pre', 'span', 'strong', 'table', 'tbody', 'td', 'th',
            'thead', 'tr', 'u', 'ul', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        ];

        $allowedAttrs = [
            'a' => ['href', 'title'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'decoding'],
            'td' => ['colspan', 'rowspan', 'align'],
            'th' => ['colspan', 'rowspan', 'align'],
            'table' => ['border', 'cellpadding', 'cellspacing'],
        ];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $sanitizeNode = function (\DOMNode $node) use (&$sanitizeNode, $allowedTags, $allowedAttrs): void {
            if ($node instanceof \DOMElement) {
                $tag = strtolower($node->tagName);

                if (! in_array($tag, $allowedTags, true)) {
                    self::unwrapNode($node);
                    return;
                }

                foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                    $name = strtolower($attribute->name);
                    $value = $attribute->value;
                    $allowedForTag = $allowedAttrs[$tag] ?? [];

                    if (! in_array($name, $allowedForTag, true)) {
                        $node->removeAttributeNode($attribute);
                        continue;
                    }

                    if ($tag === 'a' && $name === 'href' && ! preg_match('/^https?:\/\//i', $value)) {
                        $node->removeAttribute('href');
                        continue;
                    }

                    if ($tag === 'img' && $name === 'src' && ! preg_match('/^https?:\/\//i', $value)) {
                        $node->removeAttribute('src');
                    }
                }

                if ($tag === 'a') {
                    $node->setAttribute('rel', 'noopener noreferrer');
                    $node->setAttribute('target', '_blank');
                }

                if ($tag === 'img') {
                    $node->setAttribute('loading', 'lazy');
                    $node->setAttribute('decoding', 'async');
                    $node->setAttribute('style', 'max-width:100%;height:auto;');
                }
            }

            foreach (iterator_to_array($node->childNodes ?? []) as $child) {
                $sanitizeNode($child);
            }
        };

        $sanitizeNode($dom);

        return $dom->saveHTML() ?: '';
    }

    private static function unwrapNode(\DOMNode $node): void
    {
        $parent = $node->parentNode;

        if (! $parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }
}
