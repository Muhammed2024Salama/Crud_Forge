<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class RequestGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $base    = $this->outputPath('requests', app_path('Http/Requests'));

        $storeContext = array_merge($context, [
            'requestClass' => "Store{$context['model']}Request",
        ]);

        $updateContext = array_merge($context, [
            'requestClass' => "Update{$context['model']}Request",
            'rules'        => $context['updateRules'],
        ]);

        return [
            [
                'path'    => $base . DIRECTORY_SEPARATOR . "Store{$context['model']}Request.php",
                'content' => $this->render('request', $storeContext),
            ],
            [
                'path'    => $base . DIRECTORY_SEPARATOR . "Update{$context['model']}Request.php",
                'content' => $this->render('request', $updateContext),
            ],
        ];
    }
}
