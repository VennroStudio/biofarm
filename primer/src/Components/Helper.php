<?php

declare(strict_types=1);

namespace App\Components;

use Symfony\Component\Console\Output\OutputInterface;

class Helper
{
    public static function textFormatter(string $text): string
    {
        $text = str_replace('&amp;amp;', '&', $text);
        $text = str_replace('&amp;', '&', $text);
        $text = str_replace('&nbsp;', ' ', $text);
        $text = str_replace('&quot;', '\'', $text);
        $text = str_replace('\\', '', $text);

        return trim($text);
    }

    public static function translitTidal(string $str): string
    {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g',
            'д' => 'd', 'е' => 'e', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ы' => 'y', 'ъ' => '', 'э' => 'e',

            'ё' => 'yo',
            'й' => 'i',
            'ь' => '\'',
            'ю' => 'ju',
            'я' => 'ja',
        ];
        return strtr($str, $converter);
    }

    public static function bootDelay(OutputInterface $output): void
    {
        if (env('APP_ENV') === 'dev') {
            return;
        }

        $delay = rand(8, 18);
        $output->writeln('<info>boot delay: ' . $delay . ' min</info>');
        sleep($delay * 60);
    }
}
