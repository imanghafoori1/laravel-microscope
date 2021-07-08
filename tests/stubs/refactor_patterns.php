<?php

return [
    "if (!'<variable>' && '<boolean>') { return response()->'<name>'(['message' => __('<string>')], '<number>'); }" => 'Foo::bar("<1>")',
    'foo(false, true, null);' => '',
];
