<?php

declare(strict_types=1);

namespace PortalCurso\Alert\Storage;

/**
 * Armazenamento dos alertas "flash" (exibidos na próxima requisição).
 * Implemente esta interface para integrar com o session de um framework.
 */
interface FlashStorageInterface
{
    /**
     * Adiciona um alerta (já compilado em JavaScript) à fila.
     */
    public function push(string $alertJs): void;

    /**
     * Retorna e remove todos os alertas da fila.
     *
     * @return string[]
     */
    public function pull(): array;

    public function has(): bool;
}
