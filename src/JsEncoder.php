<?php

declare(strict_types=1);

namespace PortalCurso\Alert;

/**
 * Converte arrays PHP em objetos JavaScript seguros para uso dentro de <script>,
 * permitindo trechos de código bruto através de RawJs.
 */
final class JsEncoder
{
    private const FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param mixed $value
     */
    public static function encode($value): string
    {
        $raw = [];
        $prepared = self::extractRaw($value, $raw);

        $json = json_encode($prepared, self::FLAGS);
        if ($json === false) {
            throw new \InvalidArgumentException('Não foi possível converter as opções do alerta para JSON: ' . json_last_error_msg());
        }

        foreach ($raw as $placeholder => $code) {
            $json = str_replace('"' . $placeholder . '"', $code, $json);
        }

        return $json;
    }

    /**
     * @param mixed $value
     * @param array<string,string> $raw
     * @return mixed
     */
    private static function extractRaw($value, array &$raw)
    {
        if ($value instanceof RawJs) {
            $placeholder = '__PCALERT_RAW_' . count($raw) . '_' . bin2hex(random_bytes(6)) . '__';
            $raw[$placeholder] = $value->getCode();

            return $placeholder;
        }

        if ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (is_array($value)) {
            $isList = $value === [] || array_keys($value) === range(0, count($value) - 1);
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::extractRaw($v, $raw);
            }

            return $isList ? array_values($out) : (object) $out;
        }

        return $value;
    }
}
