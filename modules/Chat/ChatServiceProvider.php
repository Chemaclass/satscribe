<?php

declare(strict_types=1);

namespace Modules\Chat;

use Illuminate\Support\ServiceProvider;
use Modules\Chat\Application\AddMessageAction;
use Modules\Chat\Application\AddMessageStreamAction;
use Modules\Chat\Application\ChatFacade;
use Modules\Chat\Application\CreateChatAction;
use Modules\Chat\Application\CreateChatStreamAction;
use Modules\Chat\Domain\AddMessageActionInterface;
use Modules\Chat\Domain\AddMessageStreamActionInterface;
use Modules\Chat\Domain\ChatFacadeInterface;
use Modules\Chat\Domain\CreateChatActionInterface;
use Modules\Chat\Domain\CreateChatStreamActionInterface;
use Modules\Chat\Domain\Repository\ChatRepositoryInterface;
use Modules\Chat\Domain\Repository\FlaggedWordRepositoryInterface;
use Modules\Chat\Domain\Repository\MessageRepositoryInterface;
use Modules\Chat\Infrastructure\Repository\ChatRepository;
use Modules\Chat\Infrastructure\Repository\FlaggedWordRepository;
use Modules\Chat\Infrastructure\Repository\MessageRepository;
use Override;

final class ChatServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        AddMessageActionInterface::class => AddMessageAction::class,
        AddMessageStreamActionInterface::class => AddMessageStreamAction::class,
        CreateChatActionInterface::class => CreateChatAction::class,
        CreateChatStreamActionInterface::class => CreateChatStreamAction::class,
        ChatRepositoryInterface::class => ChatRepository::class,
        MessageRepositoryInterface::class => MessageRepository::class,
        FlaggedWordRepositoryInterface::class => FlaggedWordRepository::class,
        ChatFacadeInterface::class => ChatFacade::class,
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->when(CreateChatAction::class)
            ->needs('$trackingId')
            ->give(static fn () => tracking_id());
        $this->app->when(CreateChatAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('services.openai.max_attempts');

        $this->app->when(AddMessageAction::class)
            ->needs('$trackingId')
            ->give(static fn () => tracking_id());
        $this->app->when(AddMessageAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('services.openai.max_attempts');

        $this->app->when(AddMessageStreamAction::class)
            ->needs('$trackingId')
            ->give(static fn () => tracking_id());
        $this->app->when(AddMessageStreamAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('services.openai.max_attempts');

        $this->app->when(CreateChatStreamAction::class)
            ->needs('$trackingId')
            ->give(static fn () => tracking_id());
        $this->app->when(CreateChatStreamAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('services.openai.max_attempts');

        $this->app->when(ChatRepository::class)
            ->needs('$perPage')
            ->giveConfig('app.pagination.per_page');
        $this->app->when(ChatRepository::class)
            ->needs('$trackingId')
            ->give(static fn () => tracking_id());
    }
}
