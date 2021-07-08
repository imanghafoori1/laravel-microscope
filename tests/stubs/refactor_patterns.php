<?php

return [
    "if (!'<variable>' && '<boolean>') { return response()->'<name>'(['message' => __('<string>')], '<number>'); }"
    => 'Foo::bar("<1>", "<2>", "<3>"(), "<4>");',


    'foo(false, true, null);' => 'bar("hi");',
];
