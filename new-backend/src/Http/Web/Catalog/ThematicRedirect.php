<?php

declare(strict_types=1);
namespace App\Http\Web\Catalog;
/** Legacy thematic URLs remain reachable after the approved category rename. */
final class ThematicRedirect
{
    public const array SLUGS = [
        'pechen'=>'pechen-i-zhelchevyvodyashchaya-sistema', 'immunitet'=>'immunnaya-sistema',
        'mozg'=>'nervnaya-sistema', 'pishchevarenie'=>'pishchevaritelnaya-sistema',
        'podzheludochnaya'=>'pishchevaritelnaya-sistema', 'energiya'=>'povsednevnaya-aktivnost',
        'dykhanie'=>'dyhatelnaya-sistema', 'antioksidantnaya-zaschita'=>'antioksidanty',
        'antiparazitarnaya-programma'=>null,
    ];
    public static function target(string $path, array $query): ?string
    {
        $next=$path; $changed=false;
        $parts=explode('/',trim($path,'/'));
        $last=array_key_last($parts);
        if ($last!==null && array_key_exists($parts[$last],self::SLUGS)) {
            $slug=self::SLUGS[$parts[$last]];
            $next=$slug===null?'/catalog':'/'.implode('/',[...array_slice($parts,0,-1),$slug]);
            $changed=true;
        }
        foreach(['dlya','category'] as $key) {
            if (isset($query[$key]) && is_string($query[$key]) && array_key_exists($query[$key],self::SLUGS)) {
                $value=self::SLUGS[$query[$key]];
                if ($value===null) unset($query[$key]); else $query[$key]=$value;
                $changed=true;
            }
        }
        return $changed?$next.($query===[]?'':'?'.http_build_query($query)):null;
    }
}
