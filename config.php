<?php return array(

    // Cache the generated hierarchy.
    // Requires phile\\simpleFileDataPersistence to be enabled in your config.
    'cache' => true,

    // Sort order. Set to null to disable altogether
    // SORT_ASC, SORT_DESC
    'sort' => SORT_ASC,

    // Exclude the following top levels
    'exclude' => array(
        // 'example'
    ),

    // Prints the hierarchy (with print_r)
    'print' => false,

    // Setting config information for PhileAdmin
    'info' => array(
        'author' => array(
            'name'     => 'Dan Gibbs',
            'homepage' => 'https://github.com/gibbs'
        ),
        'namespace' => 'Gibbs',
        'url'       => 'https://github.com/Gibbs/phileSubNavigation',
        'version'   => '1.0.2',
    ),
);
