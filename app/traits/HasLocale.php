<?php

declare(strict_types=1);

/**
 * Shares the resolved locale (Layer A — UI strings) with the view layer.
 *
 * Used by TenantBaseController and any non-tenant controller (login, index,
 * error) so every page has access to {{ locale }}, {{ activeLanguage }},
 * {{ htmlLang }}, and {{ htmlDir }} in Volt.
 */
trait HasLocale
{
    protected function shareLocaleToView(): void
    {
        $locale = $this->getDI()->get('locale');
        $this->view->setVar('locale', $locale);
        $this->view->setVar('activeLanguage', $locale->getActiveLanguage());
        $this->view->setVar('htmlLang', $locale->getHtmlLang());
        $this->view->setVar('htmlDir', $locale->getDirection());
    }
}
