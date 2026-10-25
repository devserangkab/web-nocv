<?php

namespace App\Enums;

enum ApplicationFramework: string
{
    case Laravel = 'Laravel';

    case CodeIgniter = 'CodeIgniter';

    case WordPress = 'WordPress';

    case Django = 'Django';

    case SpringBoot = 'Spring Boot';

    case AspNet = 'ASP.NET';

    case Express = 'Express';

    case NextJs = 'Next.js';

    case Flutter = 'Flutter';
}
