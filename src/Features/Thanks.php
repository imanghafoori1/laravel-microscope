<?php

namespace Imanghafoori\LaravelMicroscope\Features;

use DateInterval;

class Thanks
{
    public static function shouldShow(): bool
    {
        $key = 'microscope_thanks_throttle';

        if (cache()->get($key)) {
            return false;
        }

        $show = random_int(1, 5) === 2;
        $show && cache()->set($key, '_', DateInterval::createFromDateString('3 days'));

        return $show;
    }

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
