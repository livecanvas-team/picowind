# Picowind

> [!NOTE]
>
> The default Tailwind CSS WordPress theme for LiveCanvas

A modern WordPress theme for LiveCanvas with multi-engine template support (Twig, Blade, Latte) built on Timber and Tailwind CSS.

## Requirements

- PHP >= 8.1
- Node.js >= 24.9.0
- Composer
- pnpm

## Getting Started

### 1. Clone Repository

Clone the repository to your `wp-content/themes` directory:

```bash
git clone https://github.com/livecanvas-team/picowind picowind
cd picowind
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
pnpm install
```

### 3. Development

```bash
# Start Vite dev server with HMR
pnpm run dev
```

## Template Engines

Picowind supports three template engines:

- [Twig](https://twig.symfony.com/)
- [Blade](https://laravel.com/docs/blade)
- [Latte](https://latte.nette.org/)

### Usage

#### PHP functions

You can use the PHP functions to render templates and strings with different engines.

- `Picowind\render($paths, $context, $engine, $print, $silent)` - Render template file
- `Picowind\render_string($string, $context, $engine, $print)` - Render template string

When rendering templates, Picowind will automatically detect the engine based on the file extension:

- `.twig` - Twig
- `.blade.php`, `.php`, or no extension - Blade
- `.latte` - Latte


##### Twig
```php
Picowind\render('components/card.twig', ['title' => 'Hello']);
Picowind\render_string('<div>{{ title }}</div>', ['title' => 'Hello'], 'twig');
```

##### Blade
```php
Picowind\render('components/button', ['text' => 'Click me']);
Picowind\render_string('<div>{{ $text }}</div>', ['text' => 'Hello'], 'blade');
```

##### Latte
```php
Picowind\render('components/header.latte', ['title' => 'Welcome']);
Picowind\render_string('<div>{$title}</div>', ['title' => 'Hello'], 'latte');
```

#### Shortcodes

You can also use shortcodes to render templates and strings directly in WordPress content.

##### Twig

```
[twig template="components/card.twig"]
[twig]<div>{{ site.name }}</div>[/twig]
```

##### Blade

```
[blade template="components/button"]
[blade]<div>{{ $user->name }}</div>[/blade]
```

##### Latte

```
[latte template="components/header.latte"]
[latte]<div>{$post->title}</div>[/latte]
```

## Theme Structure

```
picowind/
├── blocks/                # Block editor blocks
├── child-theme/           # Bundled child themes
├── public/                # Static assets
├── src/                   # Theme functionality (PHP) (source)
├── resources/             # Admin and frontend assets (source)
└── views/                 # Theme templates (Twig, Blade, Latte, etc.)
```

### `views/` Directory

Templates are loaded from multiple directories in order of priority:
1. **Child theme** `views/`, `blocks/`, `components/` directory (if applicable)
1. **Child theme** root directory (if applicable)
1. **Parent theme** `views/`, `blocks/`, `components/` directory
1. **Parent theme** root directory

## Miscellaneous

### Helper Functions

Picowind provides several PHP helper functions for common tasks:

- `Picowind\context()` - Get global WordPress context
- `Picowind\omni_icon($name, $attributes)` - Render SVG icons via Omni Icon plugin (supports Iconify, local uploads, and bundled icons)

### Hooks and Services

Picowind uses PHP attributes for auto-discovery of services and hooks. 

You can simply annotate your classes and methods with `#[Service]` and `#[Hook]` attributes to register them automatically.

For example:

```php
// src/Path/To/MyFeature.php

use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Core\Discovery\Attributes\Hook;

#[Service]
class MyFeature
{
    #[Hook('init', type: 'action')]
    public function my_powerful_feature(): void
    {
        // Your code here
    }
}
```

This will automatically registered and hooked into WordPress without manual intervention. It is equivalent to:

```php
// src/Path/To/MyFeature.php

class MyFeature
{
    public function __construct()
    {
        add_action('init', [$this, 'my_powerful_feature']);
    }

    public function my_powerful_feature(): void
    {
        // Your code here
    }
}
```

```php
// functions.php

require_once __DIR__ . '/src/Path/To/MyFeature.php';

$my_feature = new MyFeature();
```