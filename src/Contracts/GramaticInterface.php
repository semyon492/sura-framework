<?php

declare(strict_types=1);

namespace Sura\Contracts;

interface GramaticInterface
{
    public static function DeclName(string $name, string $declination);

    public static function totranslit(string $var, bool $lower = true, bool $punkt = true);

    public static function declOfNum(int $number, array $titles);

    public static function gramatikName(string $name);

    public static function newGram(int $num, $a, $b, $c, bool $t = false);

}