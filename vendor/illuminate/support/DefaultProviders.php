<?php

namespace PicowindDeps\Illuminate\Support;

class DefaultProviders
{
    /**
     * The current providers.
     *
     * @var array
     */
    protected $providers;
    /**
     * Create a new default provider collection.
     *
     * @return void
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [\PicowindDeps\Illuminate\Auth\AuthServiceProvider::class, \PicowindDeps\Illuminate\Broadcasting\BroadcastServiceProvider::class, \PicowindDeps\Illuminate\Bus\BusServiceProvider::class, \PicowindDeps\Illuminate\Cache\CacheServiceProvider::class, \PicowindDeps\Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class, \PicowindDeps\Illuminate\Cookie\CookieServiceProvider::class, \PicowindDeps\Illuminate\Database\DatabaseServiceProvider::class, \PicowindDeps\Illuminate\Encryption\EncryptionServiceProvider::class, \PicowindDeps\Illuminate\Filesystem\FilesystemServiceProvider::class, \PicowindDeps\Illuminate\Foundation\Providers\FoundationServiceProvider::class, \PicowindDeps\Illuminate\Hashing\HashServiceProvider::class, \PicowindDeps\Illuminate\Mail\MailServiceProvider::class, \PicowindDeps\Illuminate\Notifications\NotificationServiceProvider::class, \PicowindDeps\Illuminate\Pagination\PaginationServiceProvider::class, \PicowindDeps\Illuminate\Auth\Passwords\PasswordResetServiceProvider::class, \PicowindDeps\Illuminate\Pipeline\PipelineServiceProvider::class, \PicowindDeps\Illuminate\Queue\QueueServiceProvider::class, \PicowindDeps\Illuminate\Redis\RedisServiceProvider::class, \PicowindDeps\Illuminate\Session\SessionServiceProvider::class, \PicowindDeps\Illuminate\Translation\TranslationServiceProvider::class, \PicowindDeps\Illuminate\Validation\ValidationServiceProvider::class, \PicowindDeps\Illuminate\View\ViewServiceProvider::class];
    }
    /**
     * Merge the given providers into the provider collection.
     *
     * @param  array  $providers
     * @return static
     */
    public function merge(array $providers)
    {
        $this->providers = array_merge($this->providers, $providers);
        return new static($this->providers);
    }
    /**
     * Replace the given providers with other providers.
     *
     * @param  array  $items
     * @return static
     */
    public function replace(array $replacements)
    {
        $current = collect($this->providers);
        foreach ($replacements as $from => $to) {
            $key = $current->search($from);
            $current = is_int($key) ? $current->replace([$key => $to]) : $current;
        }
        return new static($current->values()->toArray());
    }
    /**
     * Disable the given providers.
     *
     * @param  array  $providers
     * @return static
     */
    public function except(array $providers)
    {
        return new static(collect($this->providers)->reject(fn($p) => in_array($p, $providers))->values()->toArray());
    }
    /**
     * Convert the provider collection to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->providers;
    }
}
