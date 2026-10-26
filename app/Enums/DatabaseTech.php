<?php

namespace App\Enums;

enum DatabaseTech: string
{
    case MySql = 'MySQL';

    case MariaDb = 'MariaDB';

    case PostgreSQL = 'PostgreSQL';

    case MongoDb = 'MongoDB';

    case Oracle = 'Oracle';

    case SqlServer = 'SQL Server';

    case SQLite = 'SQLite';

    case Redis = 'Redis';
}
