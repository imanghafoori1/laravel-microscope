<?php

namespace Imanghafoori\LaravelMicroscope\Features;

use DateInterval;
use Exception;
use JetBrains\PhpStorm\Pure;

class Thanks
{
    public static function shouldShow(): bool
    {
        $key = 'microscope_thanks_throttle';

        try {
            if (cache()->get($key)) {
                return false;
            }

            $show = random_int(1, 5) === 2;
            $show && cache()->set($key, '_', DateInterval::createFromDateString('2 days'));

            return $show;
        } catch (Exception $e) {
            return false;
        }
    }

    #[Pure]
    public static function messages()
    {
        return [
            '<fg=blue>|-------------------------------------------------|</>',
            '<fg=blue>|-----------     Star Me On Github     -----------|</>',
            '<fg=blue>|-------------------------------------------------|</>',
            '<fg=blue>|  Hey man, if you have found microscope useful   |</>',
            '<fg=blue>|  Please consider giving it an star on github.   |</>',
            '<fg=blue>|  \(^_^)/    Regards, Iman Ghafoori    \(^_^)/   |</>',
            '<fg=blue>|-------------------------------------------------|</>',
            'https://github.com/imanghafoori1/microscope',
        ];
    }
}
