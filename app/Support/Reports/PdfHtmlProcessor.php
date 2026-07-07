<?php

namespace App\Support\Reports;

class PdfHtmlProcessor
{
    public function process(string $html): string
    {
        return $this->mapTextNodes($html, function (string $text): string {
            if (class_exists(\ArPHP\I18N\Arabic::class)) {
                return (new \ArPHP\I18N\Arabic)->utf8Glyphs($text);
            }

            return $this->reverseArabicSegments($text);
        });
    }

    private function mapTextNodes(string $html, callable $transform): string
    {
        return preg_replace_callback(
            '/>([^<]+)</u',
            static function (array $matches) use ($transform): string {
                $segment = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (! preg_match('/[\x{0600}-\x{06FF}]/u', $segment)) {
                    return $matches[0];
                }

                $processed = $transform($segment);

                return '>'.htmlspecialchars($processed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'<';
            },
            $html
        );
    }

    private function reverseArabicSegments(string $text): string
    {
        return preg_replace_callback(
            '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{064B}-\x{065F}\x{0670}\x{0640}]+(?:[\s\d\x{060C}\x{061B}\x{061F}\x{0660}-\x{0669}\.,:؛\-]*[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{064B}-\x{065F}\x{0670}\x{0640}]+)*/u',
            static fn (array $matches): string => self::reverseUnicode($matches[0]),
            $text
        );
    }

    private static function reverseUnicode(string $value): string
    {
        return implode('', array_reverse(mb_str_split($value, 1, 'UTF-8')));
    }
}
