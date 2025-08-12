<?php

namespace tallowandsons\critter\services;

use tallowandsons\critter\Critter;
use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\CssValidationResult;
use tallowandsons\critter\models\Settings;
use yii\base\Component;

/**
 * CSS Validator service
 * Fast, safe checks executed during generation (off request path).
 */
class CssValidatorService extends Component
{
    /**
     * Validate and optionally sanitize a CSS model according to settings.
     */
    public function validate(CssModel $css): CssValidationResult
    {
        $settings = Critter::getInstance()->getSettings();

        if (!$settings->sanitizeCss) {
            return CssValidationResult::ok($css);
        }

        $raw = (string)($css->getCss() ?? '');
        $violations = [];

        // Size cap
        if (strlen($raw) > $settings->maxCssBytes) {
            $violations[] = 'maxCssBytes';
            // Too large: block to avoid perf issues or abuse
            return CssValidationResult::blocked($violations);
        }

        $sanitized = $raw;

        // Quick strip comments to reduce false positives scanning urls
        $sanitizedNoComments = preg_replace('#/\*.*?\*/#s', '', $sanitized) ?? $sanitized;

        // Denylist patterns
        $deny = [
            '/expression\s*\(/i',
            '/behavior\s*\:/i',
            '/javascript\s*:/i',
            '/@import\s+url\s*\(/i',
            '/@import\s+[\"\"][^\"\"]+[\"\"]/i',
            '/-moz-binding\s*:/i',
        ];

        $matchedDeny = false;
        foreach ($deny as $pattern) {
            if (preg_match($pattern, $sanitizedNoComments)) {
                $violations[] = 'denylist:' . trim($pattern, '/');
                $matchedDeny = true;
            }
        }

        if ($matchedDeny) {
            // remove dangerous @import lines entirely
            $sanitized = preg_replace('/^\s*@import[^;]*;\s*$/mi', '', $sanitized) ?? $sanitized;
            // remove behavior properties
            $sanitized = preg_replace('/behavior\s*:\s*[^;]+;?/i', '', $sanitized) ?? $sanitized;
            // neutralize expression(
            $sanitized = preg_replace('/expression\s*\(/i', '/*expr*/(', $sanitized) ?? $sanitized;
            // neutralize javascript:
            $sanitized = preg_replace('/javascript\s*:/i', '/*js:*/', $sanitized) ?? $sanitized;
        }

        // url() checks
        $urlRegex = '/url\(\s*([\"\"]?)([^\)\"\"]+)\1\s*\)/i';
        $sanitized = preg_replace_callback($urlRegex, function ($m) use ($settings, &$violations) {
            $u = trim($m[2]);
            // data URLs
            if (str_starts_with(strtolower($u), 'data:')) {
                if (!$settings->allowDataUrls) {
                    $violations[] = 'dataUrl:notAllowed';
                    return 'url(about:blank)';
                }
                $payloadPos = strpos($u, ',');
                if ($payloadPos !== false) {
                    $payload = substr($u, $payloadPos + 1);
                    if (strlen($payload) > $settings->dataUrlMaxBytes) {
                        $violations[] = 'dataUrl:tooLarge';
                        return 'url(about:blank)';
                    }
                }
                return $m[0];
            }

            // absolute http(s)
            if (preg_match('#^https?://#i', $u)) {
                if (!empty($settings->allowedExternalHosts)) {
                    $host = parse_url($u, PHP_URL_HOST) ?: '';
                    if (!in_array(strtolower($host), array_map('strtolower', $settings->allowedExternalHosts), true)) {
                        $violations[] = 'externalHost:notAllowed:' . $host;
                        return 'url(about:blank)';
                    }
                } else {
                    // default policy: block cross-origin
                    $violations[] = 'externalHost:blocked';
                    return 'url(about:blank)';
                }
            }

            // otherwise leave as-is
            return $m[0];
        }, $sanitized) ?? $sanitized;

        // If strict mode, lightly collapse excessive whitespace/newlines (cheap "minify")
        if ($settings->strictMode) {
            $sanitized = preg_replace("#\s{3,}#", '  ', $sanitized) ?? $sanitized;
        }

        // Decide outcome
        if (empty($violations)) {
            return CssValidationResult::ok(new CssModel($sanitized));
        }

        // If there were deny hits but we sanitized, return sanitized
        return CssValidationResult::sanitized(new CssModel($sanitized), $violations);
    }
}
