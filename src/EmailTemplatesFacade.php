<?php

declare(strict_types=1);

namespace Topol\EmailTemplates;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Topol\EmailTemplates\EmailTemplates
 */
class EmailTemplatesFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'email-templates';
    }
}
