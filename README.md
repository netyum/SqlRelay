SqlRelay For Yii
========

A Yii Extension to simulate the Oracle PDO driver using the SqlRelay PHP functions.


# Usage

1. copy all files to /path/to/app/protected/components

2. modify /path/to/app/protected/config/main.php
```
// application components
    'components'=>array(        
        'db'=>array(
		'class'=>'SqlRelayConnection',
		// connectionString format: sqlr:instanceName:port:sockt
		'connectionString' => 'sqlr:example:110000:/tmp/example.socket',
		'emulatePrepare' => true,
		'username' => 'scott',
		'password' => 'tiger',
		'tablePrefix' => 'TBL_',
		'schemaCachingDuration'=>3600,
		'enableProfiling'=>true,
        ),
```
