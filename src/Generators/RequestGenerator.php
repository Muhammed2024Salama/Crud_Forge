<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class RequestGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $storeContext = array_merge($context, ['requestClass' => "Store{$context['model']}Request"]);
        $updateContext = array_merge($context, ['requestClass' => "Update{$context['model']}Request"]);

        return [
            [
                'path' => app_path("Http/Requests/Store{$context['model']}Request.php"),
                'content' => $this->render('request', $storeContext),
            ],
            [
                'path' => app_path("Http/Requests/Update{$context['model']}Request.php"),
                'content' => $this->render('request', $updateContext),
            ],
        ];
    }
}
