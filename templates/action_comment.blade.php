@php
    $all_methods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    ($all_methods == $methods) && ($methods = ['any']);
@endphp
        /**
@if ($file)
         * @at({!! $file !!}:{!! $line !!})
@endif
@if (\count($methods) > 1)
         * @methods({!! \implode(', ', $methods) !!})
         * @uri(/{!! $url !!})
@else
         * {!! '@'.strtolower(\implode('', $methods)) !!}(/{!! $url !!})
@endif
         * @name({!! $routeName !!})
         * @middlewares({!! implode(', ', $middlewares) !!})
         */
