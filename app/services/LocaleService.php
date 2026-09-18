<?php

declare(strict_types=1);

use Phalcon\Di\Injectable;
use Phalcon\Translate\Adapter\NativeArray;
use Phalcon\Translate\InterpolatorFactory;

class LocaleService extends Injectable
{
    protected NativeArray $translator;
    protected string $activeLang;
    protected string $fallbackLang;

    public function __construct()
    {
        $config = $this->getDI()->get('config');
        $this->fallbackLang = $config->path('languages.fallback', 'en');
        $default            = $config->path('languages.default', 'th');

        // Resolve active language: explicit session choice > default (Thai)
        $sessionLang = $this->session->get('active_language');
        $this->activeLang = $this->isSupported($sessionLang) ? $sessionLang : $default;

        $this->translator = $this->loadCatalog($this->activeLang);
    }

    /**
     * Translate a key, with optional sprintf-style placeholders.
     * Falls back to the fallback language, then to the key itself.
     */
    public function t(string $key, array $params = []): string
    {
        $value = $this->translator->_($key, $params);

        // NativeArray returns the key itself when missing — fall back if needed
        if ($value === $key && $this->activeLang !== $this->fallbackLang) {
            $fallback = $this->loadCatalog($this->fallbackLang);
            $value = $fallback->_($key, $params);
        }

        return $value;
    }

    public function getActiveLanguage(): string
    {
        return $this->activeLang;
    }

    public function getHtmlLang(): string
    {
        return $this->activeLang;
    }

    public function getLocale(): string
    {
        return $this->getDI()->get('config')
            ->path("languages.supported.{$this->activeLang}.locale", 'th_TH');
    }

    public function getDirection(): string
    {
        return $this->getDI()->get('config')
            ->path("languages.supported.{$this->activeLang}.dir", 'ltr');
    }

    public function isSupported(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        $supported = $this->getDI()->get('config')->path('languages.supported');
        return $supported && $supported->offsetExists($code);
    }

    protected function loadCatalog(string $lang): NativeArray
    {
        $file = APP_PATH . '/lang/' . $lang . '.php';
        $messages = file_exists($file) ? require $file : [];

        return new NativeArray(new InterpolatorFactory(), ['content' => $messages]);
    }
}
