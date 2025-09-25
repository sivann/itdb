<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\FileModel;
use App\Services\AuthService;
use App\Services\DatabaseManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class FileController extends BaseController
{
    private AuthService $authService;
    private FileModel $fileModel;
    private DatabaseManager $db;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        FileModel $fileModel,
        DatabaseManager $db
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->fileModel = $fileModel;
        $this->db = $db;
    }

    /**
     * List all files
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);

        // Build filters for FileModel
        $filters = [];
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }
        if (!empty($queryParams['type'])) {
            $filters['type'] = $queryParams['type'];
        }
        if (!empty($queryParams['uploader'])) {
            $filters['uploader'] = $queryParams['uploader'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = 20;

        // Get paginated files using FileModel
        $result = $this->fileModel->getPaginated($page, $perPage, $filters);

        // Get uploaders for filter dropdown - use FileModel method
        $uploaders = $this->fileModel->getUploaders();

        return $this->render($response, 'files/index.twig', [
            'user' => $user,
            'files' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'search' => $queryParams['search'] ?? '',
            'type_filter' => $queryParams['type'] ?? '',
            'uploader_filter' => $queryParams['uploader'] ?? '',
            'uploaders' => $uploaders,
        ]);
    }

    /**
     * Show file details (redirects to edit)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $queryParams = $this->getQueryParams($request);

        // Preserve query parameters when redirecting
        $queryString = http_build_query($queryParams);
        $redirectUrl = '/files/' . $id . '/edit' . ($queryString ? '?' . $queryString : '');

        return $response->withStatus(302)->withHeader('Location', $redirectUrl);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get file types for dropdown
        $fileTypes = $this->fileModel->getFileTypes();

        return $this->render($response, 'files/edit.twig', [
            'mode' => 'create',
            'user' => $user,
            'file_types' => $fileTypes,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new file
     */
    public function store(Request $request, Response $response): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'files.create');
        }

        $data = $this->getParsedBody($request);
        $user = $this->authService->getCurrentUser();

        // Debug logging
        $this->logger->info('File upload debug', [
            'ajax_upload' => $data['ajax_upload'] ?? 'not_set',
            'has_ajax_field' => isset($data['ajax_upload']),
            'data_keys' => array_keys($data)
        ]);

        // Validation
        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }
        if (empty($data['ftype'])) {
            $errors[] = 'File type is required';
        }

        // Handle file upload
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'] ?? null;

        if (!$uploadedFile || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload is required';
        }

        if (!empty($errors)) {
            // Check if this is an AJAX request (via hidden field)
            $isAjax = !empty($data['ajax_upload']);

            if ($isAjax) {
                // Return JSON error response for AJAX requests
                return $this->json($response->withStatus(400), [
                    'success' => false,
                    'error' => implode('; ', $errors)
                ]);
            }

            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            // Get original filename and sanitize extension
            $originalName = $uploadedFile->getClientFilename();
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $sanitizedExtension = preg_replace('/[^a-z0-9]/', '', $extension);

            // Convert ftype to integer
            $ftypeInt = !empty($data['ftype']) ? (int) $data['ftype'] : null;

            // Create temporary file record to get ID
            $tempFileId = $this->fileModel->create([
                'ftype' => $ftypeInt,
                'type' => $this->getFileTypeName($ftypeInt), // Legacy type field
                'title' => $this->sanitizeString($data['title']),
                'filename' => $originalName,
                'description' => $this->sanitizeString($data['description'] ?? ''),
                'fname' => 'temp', // Will be updated after generating proper name
                'filesize' => $uploadedFile->getSize(),
                'uploader' => $user->username,
                'uploaddate' => time(),
                'date' => !empty($data['date']) ? strtotime($data['date']) : time(),
            ]);

            // Generate proper filename: ID_filetype_randomstring.extension
            $fileTypeCode = $this->getFileTypeCode($ftypeInt);
            $randomString = substr(uniqid(), -8);
            $properFilename = sprintf('%d_%s_%s.%s',
                $tempFileId,
                $fileTypeCode,
                $randomString,
                $sanitizedExtension
            );

            // Create upload directory if it doesn't exist
            $uploadPath = $_ENV['UPLOAD_PATH'] ?? './public/storage/uploads';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Move uploaded file with proper filename
            $uploadedFile->moveTo($uploadPath . '/' . $properFilename);

            // Update the file record with proper filename
            $this->fileModel->update($tempFileId, [
                'fname' => $properFilename
            ]);

            $fileId = $tempFileId;

            $this->logUserAction('file_created', ['file_id' => $fileId]);

            // Check if this is an AJAX request (via hidden field)
            $isAjax = !empty($data['ajax_upload']);
            $this->logger->info('AJAX detection (success)', ['isAjax' => $isAjax, 'ajax_upload_value' => $data['ajax_upload'] ?? 'missing']);

            if ($isAjax) {
                // Return JSON response for AJAX requests
                return $this->json($response, [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'file' => [
                        'id' => $fileId,
                        'fname' => $properFilename,
                        'title' => $this->sanitizeString($data['title']),
                        'ftype' => $ftypeInt,
                    ]
                ]);
            }

            $this->addFlashMessage('success', 'File uploaded successfully');
            return $this->redirectToRoute($request, $response, 'files.edit', ['id' => $fileId]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating file', ['error' => $e->getMessage()]);

            // Check if this is an AJAX request (via hidden field)
            $isAjax = !empty($data['ajax_upload']);

            if ($isAjax) {
                // Return JSON error response for AJAX requests
                return $this->json($response->withStatus(400), [
                    'success' => false,
                    'error' => 'Error uploading file'
                ]);
            }

            $this->addFlashMessage('error', 'Error uploading file');
            return $this->redirectToRoute($request, $response, 'files.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $file = $this->fileModel->find($id);
        if (!$file) {
            $this->addFlashMessage('error', 'File not found');
            return $this->redirectToRoute($request, $response, 'files.index');
        }

        // Add file existence and path information
        $file['file_exists'] = $this->fileModel->fileExists($file);
        $file['file_path'] = $this->fileModel->getFilePath($file);
        $file['file_size'] = $this->fileModel->getFileSize($file);

        // Get file types for dropdown
        $fileTypes = $this->fileModel->getFileTypes();

        return $this->render($response, 'files/edit.twig', [
            'mode' => 'edit',
            'user' => $user,
            'file' => $file,
            'file_types' => $fileTypes,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update file
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'files.index');
        }

        $id = (int) $args['id'];
        $data = $this->getParsedBody($request);

        $file = $this->fileModel->find($id);
        if (!$file) {
            $this->addFlashMessage('error', 'File not found');
            return $this->redirectToRoute($request, $response, 'files.index');
        }

        // Validation
        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            // Convert ftype to integer
            $ftypeInt = !empty($data['ftype']) ? (int) $data['ftype'] : null;

            $updateData = [
                'ftype' => $ftypeInt,
                'type' => $this->getFileTypeName($ftypeInt), // Legacy type field
                'title' => $this->sanitizeString($data['title']),
                'description' => $this->sanitizeString($data['description'] ?? ''),
            ];

            if (!empty($data['date'])) {
                $updateData['date'] = strtotime($data['date']);
            }

            $this->fileModel->update($id, $updateData);

            $this->logUserAction('file_updated', ['file_id' => $id]);
            $this->addFlashMessage('success', 'File updated successfully');
            return $this->redirectToRoute($request, $response, 'files.edit', ['id' => $file->id]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating file', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating file');
            return $this->redirectToRoute($request, $response, 'files.edit', ['id' => $id]);
        }
    }

    /**
     * Delete file
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'files.index');
        }

        $id = (int) $args['id'];

        $file = $this->fileModel->find($id);
        if (!$file) {
            $this->addFlashMessage('error', 'File not found');
            return $this->redirectToRoute($request, $response, 'files.index');
        }

        try {
            // Delete physical file if it exists
            if ($file->file_exists) {
                unlink($file->file_path);
            }

            $this->logUserAction('file_deleted', ['file_id' => $id, 'title' => $file['title']]);
            $this->fileModel->delete($id);

            $this->addFlashMessage('success', 'File deleted successfully');
            return $this->redirectToRoute($request, $response, 'files.index');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting file', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting file');
            return $this->redirectToRoute($request, $response, 'files.edit', ['id' => $id]);
        }
    }

    /**
     * Download file
     */
    public function download(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $file = $this->fileModel->find($id);
        if (!$file) {
            $this->addFlashMessage('error', 'File not found');
            return $this->redirectToRoute($request, $response, 'files.index');
        }

        $filePath = $this->fileModel->getFilePath($file);
        if (!$this->fileModel->fileExists($file)) {
            $this->addFlashMessage('error', 'File does not exist on disk');
            return $this->redirectToRoute($request, $response, 'files.edit', ['id' => $id]);
        }

        try {
            $this->logUserAction('file_downloaded', ['file_id' => $file['id']]);

            $fileContent = file_get_contents($filePath);
            $response->getBody()->write($fileContent);

            // Use original filename for download or title as fallback
            $downloadName = $file['filename'] ?: $file['title'];

            return $response
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $downloadName . '"')
                ->withHeader('Content-Length', (string) strlen($fileContent));

        } catch (\Exception $e) {
            $this->logger->error('Error downloading file', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error downloading file');
            return $this->redirectToRoute($request, $response, 'files.edit', ['id' => $id]);
        }
    }

    /**
     * API endpoint for searching files
     */
    public function search(Request $request, Response $response): Response
    {
        $queryParams = $this->getQueryParams($request);
        $query = $queryParams['q'] ?? '';
        $limit = min(20, max(1, (int) ($queryParams['limit'] ?? 20)));
        $excludeSoftware = $queryParams['exclude_software'] ?? null;

        // Build search filters
        $searchFilters = [];
        if (!empty($query)) {
            $searchFilters['search'] = $query;
        }
        if ($excludeSoftware) {
            $searchFilters['exclude_software'] = $excludeSoftware;
        }

        // Use FileModel for search
        $files = $this->fileModel->search($searchFilters, $limit);

        // Debug logging
        $this->logger->info('File search debug', [
            'query' => $query,
            'exclude_software' => $excludeSoftware,
            'limit' => $limit,
            'files_found' => count($files)
        ]);

        // Format results for frontend
        $results = [];
        foreach ($files as $file) {
            // Debug logging
            $this->logger->info('File uploader debug', [
                'file_id' => $file['id'],
                'uploader_raw' => $file['uploader'] ?? 'missing',
                'uploader_type' => gettype($file['uploader']),
                'uploader_value' => $file['uploader']
            ]);

            $results[] = [
                'id' => $file['id'],
                'title' => $file['title'] ?: 'Untitled File',
                'display' => sprintf('ID: %d, %s',
                    $file['id'],
                    $file['title'] ?: 'Untitled File'
                ),
                'fname' => $file['fname'],
                'type' => $file['type'],
                'fileType' => ['name' => $file['type_name'] ?: 'Unknown'],
                'size_formatted' => isset($file['filesize']) && $file['filesize'] ? $this->formatBytes($file['filesize']) : 'Unknown Size',
                'uploader' => $this->getUploaderName($file),
                'upload_date' => $file['uploaddate'] ? date('Y-m-d', $file['uploaddate']) : null
            ];
        }

        return $this->json($response, [
            'success' => true,
            'files' => $results
        ]);
    }

    /**
     * Get uploader name handling mixed data (usernames and user IDs)
     */
    private function getUploaderName($file): ?string
    {
        // Get raw uploader value from database
        $uploaderValue = $file['uploader'] ?? null;
        if (!$uploaderValue) {
            return null;
        }

        // If it's numeric, treat as user ID and look up username
        if (is_numeric($uploaderValue)) {
            $user = $this->db->fetchOne("SELECT username FROM users WHERE id = :id", ['id' => (int) $uploaderValue]);
            return $user ? $user['username'] : "Unknown User (ID: $uploaderValue)";
        }

        // Otherwise, treat as username
        return $uploaderValue;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        if ($bytes === 0 || $bytes === null) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $base = log($bytes, 1024);
        $index = floor($base);

        return round(pow(1024, $base - $index), $precision) . ' ' . $units[$index];
    }

    /**
     * Get file type code for filename generation
     */
    private function getFileTypeCode(?int $fileTypeId): string
    {
        if (!$fileTypeId) {
            return 'other';
        }

        try {
            $fileType = $this->db->fetchOne("SELECT typedesc FROM filetypes WHERE id = :id", ['id' => $fileTypeId]);
            if ($fileType) {
                // Sanitize the type description for filename use
                return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fileType['typedesc']));
            }
        } catch (\Exception $e) {
            // If lookup fails, use 'other'
        }

        return 'other';
    }

    /**
     * Get file type name for legacy type field
     */
    private function getFileTypeName(?int $fileTypeId): ?string
    {
        if (!$fileTypeId) {
            return null;
        }

        try {
            $fileType = $this->db->fetchOne("SELECT typedesc FROM filetypes WHERE id = :id", ['id' => $fileTypeId]);
            if ($fileType) {
                return $fileType['typedesc'];
            }
        } catch (\Exception $e) {
            // If lookup fails, return null
        }

        return null;
    }
}