<?php

declare(strict_types=1);

namespace App;

/**
 * Renders plain PHP templates from templates/. Variables are extracted into
 * the template scope; always escape output with e().
 */
final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(private string $dir)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = 'layout'): string
    {
        $content = $this->partial($template, $data);
        if ($layout === null) {
            return $content;
        }
        return $this->partial($layout, ['content' => $content] + $data);
    }

    /** @param array<string, mixed> $data */
    public function partial(string $template, array $data = []): string
    {
        $__file = $this->dir . '/' . $template . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException("Template not found: $template");
        }
        $view = $this;
        extract($data + $this->shared, EXTR_SKIP);
        ob_start();
        try {
            require $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
