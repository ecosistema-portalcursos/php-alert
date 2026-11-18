<?php

declare(strict_types=1);

namespace PortalCurso\Alert;

/**
 * Representa um trecho de JavaScript que deve ser inserido sem aspas
 * (ex.: funções para preConfirm, inputValidator, didOpen...).
 *
 * ATENÇÃO: nunca coloque dados vindos do usuário dentro de RawJs.
 */
final class RawJs
{
    /** @var string */
    private $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
