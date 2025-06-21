<?php

declare(strict_types=1);

namespace Modules\Faq\Application;

use Modules\Faq\Domain\Repository\FaqRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class FaqService
{
    public function __construct(private FaqRepositoryInterface $repository)
    {
    }

    /**
     * Fetch FAQ data for the given search term.
     *
     * @return array<string, mixed>
     */
    public function getFaqData(string $search): array
    {
        $faqs = $this->repository->getCollectionBySearch($search);
        if ($faqs->isEmpty()) {
            throw new NotFoundHttpException();
        }

        return [
            'search' => $search,
            'faqs' => $faqs,
            'categories' => $this->repository->getCategories($faqs),
        ];
    }
}
