<?php

declare(strict_types=1);

namespace App\Handlers;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class HtmlErrorRenderer implements ErrorRendererInterface
{
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $statusCode = 500;
        $reasonPhrase = 'Internal Server Error';

        if ($exception instanceof HttpNotFoundException) {
            $statusCode = 404;
            $reasonPhrase = 'Not Found';

            // For 404s, return a simple message without stack trace
            return $this->render404();
        }

        // For other errors, show details only if debugging is enabled
        if ($displayErrorDetails) {
            return $this->renderDetailedError($exception, $statusCode, $reasonPhrase);
        }

        return $this->renderSimpleError($statusCode, $reasonPhrase);
    }

    private function render404(): string
    {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title text-primary">404</h1>
                        <h4 class="card-subtitle mb-3 text-muted">Page Not Found</h4>
                        <p class="card-text">The requested resource could not be found.</p>
                        <a href="/" class="btn btn-primary">Go Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function renderSimpleError(int $statusCode, string $reasonPhrase): string
    {
        return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Error $statusCode</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
</head>
<body class=\"bg-light\">
    <div class=\"container mt-5\">
        <div class=\"row justify-content-center\">
            <div class=\"col-md-6 text-center\">
                <div class=\"card\">
                    <div class=\"card-body\">
                        <h1 class=\"card-title text-danger\">$statusCode</h1>
                        <h4 class=\"card-subtitle mb-3 text-muted\">$reasonPhrase</h4>
                        <p class=\"card-text\">An error occurred while processing your request.</p>
                        <a href=\"/\" class=\"btn btn-primary\">Go Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
    }

    private function renderDetailedError(Throwable $exception, int $statusCode, string $reasonPhrase): string
    {
        return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Error $statusCode</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
</head>
<body class=\"bg-light\">
    <div class=\"container mt-5\">
        <div class=\"row\">
            <div class=\"col-12\">
                <div class=\"card\">
                    <div class=\"card-header\">
                        <h1 class=\"card-title text-danger\">$statusCode $reasonPhrase</h1>
                    </div>
                    <div class=\"card-body\">
                        <h5>Error Details:</h5>
                        <p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>
                        <p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>
                        <p><strong>Line:</strong> " . $exception->getLine() . "</p>

                        <h5 class=\"mt-4\">Stack Trace:</h5>
                        <pre class=\"bg-dark text-light p-3 rounded\"><code>" . htmlspecialchars($exception->getTraceAsString()) . "</code></pre>

                        <a href=\"/\" class=\"btn btn-primary mt-3\">Go Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
    }
}