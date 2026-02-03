<?php

declare(strict_types=1);

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;

use function Picowind\context;
use function Picowind\render;
use function Picowind\render_string;

#[Service]
class Shortcode
{
    #[Hook('init', type: 'action')]
    public function register_shortcode(): void
    {
        if (! apply_filters('f!picowind/supports/shortcode:register', true)) {
            return;
        }

        add_shortcode('twig', [$this, 'render_twig_shortcode']);
        add_shortcode('blade', [$this, 'render_blade_shortcode']);
        add_shortcode('latte', [$this, 'render_latte_shortcode']);
    }

    public function render_twig_shortcode($atts, $content = null): string
    {
        $atts = shortcode_atts(
            [
                'template' => null,
            ],
            $atts,
            'twig',
        );

        $globalContext = context();

        if ($atts['template']) {
            return render($atts['template'], $globalContext, 'twig', false) ?? '';
        }

        if ($content) {
            return render_string($content, $globalContext, 'twig', false) ?? '';
        }

        return '';
    }

    public function render_blade_shortcode($atts, $content = null): string
    {
        $atts = shortcode_atts(
            [
                'template' => null,
            ],
            $atts,
            'blade',
        );

        $globalContext = context();

        if ($atts['template']) {
            return render($atts['template'], $globalContext, 'blade', false) ?? '';
        }

        if ($content) {
            return render_string($content, $globalContext, 'blade', false) ?? '';
        }

        return '';
    }

    public function render_latte_shortcode($atts, $content = null): string
    {
        $atts = shortcode_atts(
            [
                'template' => null,
            ],
            $atts,
            'latte',
        );

        $globalContext = context();

        if ($atts['template']) {
            return render($atts['template'], $globalContext, 'latte', false) ?? '';
        }

        if ($content) {
            return render_string($content, $globalContext, 'latte', false) ?? '';
        }

        return '';
    }
}
